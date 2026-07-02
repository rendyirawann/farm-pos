<?php

namespace App\Http\Controllers\Backend\Billing;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Tenancy\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * Halaman langganan: status sekarang + pilihan paket + riwayat pembayaran.
     */
    public function index()
    {
        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan untuk akun ini.');
        }

        $plans = Plan::all();
        $history = Subscription::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $clientKey = config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production', false);

        return view('backend.billing.index', compact('tenant', 'plans', 'history', 'clientKey', 'isProduction'));
    }

    /**
     * Buat transaksi langganan + Snap Token Midtrans.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:' . implode(',', array_keys(Plan::all()))],
        ]);

        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant tidak ditemukan.'], 404);
        }

        $planKey = $request->plan;

        // Paket konsultasi (Customize) tidak melalui checkout Midtrans.
        if (Plan::isContact($planKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paket ' . Plan::name($planKey) . ' diaktifkan via konsultasi. Silakan hubungi kami melalui WhatsApp.',
            ], 422);
        }

        $amount  = Plan::price($planKey);

        try {
            $subscription = DB::transaction(function () use ($tenant, $planKey, $amount) {
                $orderId = 'DSP-SUB-' . strtoupper(Str::random(6)) . '-' . $tenant->id . '-' . substr((string) Str::uuid(), 0, 8);

                return Subscription::create([
                    'tenant_id'         => $tenant->id,
                    'plan'              => $planKey,
                    'amount'            => $amount,
                    'billing_period'    => 'monthly',
                    'status'            => 'pending',
                    'midtrans_order_id' => $orderId,
                ]);
            });

            $this->configureMidtrans();

            $params = [
                'transaction_details' => [
                    'order_id'     => $subscription->midtrans_order_id,
                    'gross_amount' => (int) $amount,
                ],
                'item_details' => [[
                    'id'       => 'plan-' . $planKey,
                    'price'    => (int) $amount,
                    'quantity' => 1,
                    'name'     => 'Langganan ' . Plan::name($planKey) . ' (1 bulan)',
                ]],
                'customer_details' => [
                    'first_name' => Auth::user()->name,
                    'email'      => Auth::user()->email,
                    'phone'      => $tenant->phone,
                ],
                'callbacks' => [
                    'finish' => route('billing.index'),
                ],
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $subscription->update(['snap_token' => $snapToken]);

            return response()->json([
                'status'     => 'success',
                'snap_token' => $snapToken,
                'order_id'   => $subscription->midtrans_order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription checkout failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Webhook Midtrans untuk langganan (route publik, dikecualikan dari CSRF).
     */
    public function webhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');

        // 1. Verifikasi signature (anti-pemalsuan)
        $expected = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        if (!hash_equals($expected, (string) $request->signature_key)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. Cari subscription (tanpa scope tenant — model ini memang lintas konteks)
        $subscription = Subscription::where('midtrans_order_id', $request->order_id)->first();
        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $transaction = $request->transaction_status;
        $fraud       = $request->fraud_status;

        // 3. Aktivasi saat pembayaran sukses
        if (($transaction === 'capture' && $fraud === 'accept') || $transaction === 'settlement') {
            $this->activateSubscription($subscription, $request->payment_type);
        } elseif (in_array($transaction, ['cancel', 'deny', 'expire'], true)) {
            $subscription->update(['status' => 'failed']);
        } elseif ($transaction === 'pending') {
            $subscription->update(['status' => 'pending', 'payment_type' => $request->payment_type]);
        }

        return response()->json(['message' => 'OK']);
    }

    private function activateSubscription(Subscription $subscription, ?string $paymentType): void
    {
        if ($subscription->status === 'paid') {
            return; // idempoten
        }

        DB::transaction(function () use ($subscription, $paymentType) {
            $tenant = $subscription->tenant;

            // Perpanjang dari sisa masa aktif jika masih berlaku
            $base = ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture())
                ? $tenant->subscription_ends_at->copy()
                : now();
            $endsAt = $base->addMonthNoOverflow();

            $subscription->update([
                'status'       => 'paid',
                'payment_type' => $paymentType,
                'paid_at'      => now(),
                'starts_at'    => now(),
                'ends_at'      => $endsAt,
            ]);

            $tenant->update([
                'plan'                 => $subscription->plan,
                'subscription_status'  => 'active',
                'subscription_ends_at' => $endsAt,
                'is_active'            => true,
            ]);
        });
    }

    private function configureMidtrans(): void
    {
        \Midtrans\Config::$serverKey    = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;

        // URL notifikasi opsional (dari config/env). Jika kosong, Midtrans memakai
        // Notification URL yang diset di dashboard Midtrans (arahkan ke /api/subscription-webhook).
        $notifyUrl = config('services.midtrans.notify_url');
        if (!empty($notifyUrl)) {
            \Midtrans\Config::$overrideNotifUrl = $notifyUrl;
        }
    }
}
