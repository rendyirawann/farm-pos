<?php

namespace App\Http\Controllers\Backend\Billing;

use App\Http\Controllers\Controller;
use App\Models\DepositTopup;
use App\Models\DepositTransaction;
use App\Services\DepositService;
use App\Tenancy\DepositConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    public function __construct(private DepositService $deposit)
    {
    }

    /** Halaman plan deposit: saldo poin, pilihan top-up, aturan, riwayat. */
    public function index()
    {
        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan untuk akun ini.');
        }

        $tiers   = $this->deposit->tierOptions($tenant);
        $history = DepositTransaction::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('backend.billing.deposit', [
            'tenant'           => $tenant,
            'tiers'            => $tiers,
            'history'          => $history,
            'fee'              => DepositConfig::feePerTransaction(),
            'maxPoints'        => DepositConfig::maxPoints(),
            'minDeposit'       => DepositConfig::minDeposit(),
            'expiryDays'       => DepositConfig::expiryDays(),
            'purchaseEnabled'  => (bool) config('billing.purchase_enabled', false),
            'maintenanceText'  => config('billing.maintenance_text', 'Segera hadir.'),
            'clientKey'        => config('services.midtrans.client_key'),
            'isProduction'     => (bool) config('services.midtrans.is_production', false),
            'monthlyActive'    => $tenant->monthlyActive(),
            'needsInitial'     => $this->deposit->needsInitialTopup($tenant),
            'initialTopup'     => DepositConfig::initialTopup(),
            'initialPoints'    => (int) DepositConfig::pointsForTopup(DepositConfig::initialTopup()),
            'minTopup'         => DepositConfig::minDeposit(),
            'manualWa'         => DepositConfig::manualWa(),
            'manualBank'       => DepositConfig::manualBank(),
        ]);
    }

    /** Buat transaksi top-up + Snap Token Midtrans. Cap ditegakkan di sini. */
    public function checkout(Request $request)
    {
        if (! config('billing.purchase_enabled', false)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Top-up deposit sementara dinonaktifkan. ' . config('billing.maintenance_text', ''),
            ], 503);
        }

        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant tidak ditemukan.'], 404);
        }

        $amount = (int) $request->input('amount', 0);

        // Aktivasi: akun deposit baru WAJIB top-up awal sebesar initialTopup (mis. 50.000).
        // Setelah aktif, boleh top-up nominal bebas (>= minimal).
        if ($this->deposit->needsInitialTopup($tenant)) {
            $initial = DepositConfig::initialTopup();
            if ($amount !== $initial) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Aktivasi plan deposit mewajibkan top-up awal Rp' . number_format($initial, 0, ',', '.')
                        . ' (dapat ' . number_format((int) DepositConfig::pointsForTopup($initial), 0, ',', '.') . ' poin). Silakan pilih nominal tersebut.',
                ], 422);
            }
        } else {
            $min = DepositConfig::minDeposit();
            if ($amount < $min) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Minimal top-up Rp' . number_format($min, 0, ',', '.') . '.',
                ], 422);
            }
        }

        // Poin dihitung server-side (anti-manipulasi): cocok tier -> poin tier (bonus);
        // selain itu -> 1:1 (poin = nominal).
        $points = DepositConfig::pointsForTopup($amount);
        if ($points === null) {
            return response()->json(['status' => 'error', 'message' => 'Nominal top-up tidak valid.'], 422);
        }

        // Cek batas maksimum saldo poin (null = tanpa batas -> selalu lolos).
        if (! $this->deposit->canTopUp($tenant, $points)) {
            $opt = $this->deposit->tierOptions($tenant);
            $msg = $opt['any_fits']
                ? 'Top-up ini akan melebihi batas maksimum poin (Rp' . number_format($opt['max'], 0, ',', '.')
                    . '). Pilih nominal lebih kecil (maks yang muat: Rp' . number_format($opt['recommended'], 0, ',', '.') . ').'
                : 'Saldo poin Anda sudah mendekati batas maksimum (Rp' . number_format($opt['max'], 0, ',', '.')
                    . '). Tidak ada nominal top-up yang muat saat ini. Pakai poin dulu, lalu top-up lagi.';
            return response()->json(['status' => 'error', 'message' => $msg], 422);
        }

        try {
            $topup = DB::transaction(function () use ($tenant, $amount, $points) {
                $orderId = 'DSP-DEP-' . strtoupper(Str::random(6)) . '-' . $tenant->id . '-' . substr((string) Str::uuid(), 0, 8);

                return DepositTopup::create([
                    'tenant_id'         => $tenant->id,
                    'amount'            => $amount,
                    'points'            => $points,
                    'status'            => 'pending',
                    'midtrans_order_id' => $orderId,
                ]);
            });

            $this->configureMidtrans();

            $params = [
                'transaction_details' => [
                    'order_id'     => $topup->midtrans_order_id,
                    'gross_amount' => $amount,
                ],
                'item_details' => [[
                    'id'       => 'deposit-' . $amount,
                    'price'    => $amount,
                    'quantity' => 1,
                    'name'     => 'Top-up Deposit (' . number_format($points, 0, ',', '.') . ' poin)',
                ]],
                'customer_details' => [
                    'first_name' => Auth::user()->name,
                    'email'      => Auth::user()->email,
                    'phone'      => $tenant->phone,
                ],
                'callbacks' => [
                    'finish' => route('deposit.index'),
                ],
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $topup->update(['snap_token' => $snapToken]);

            return response()->json([
                'status'     => 'success',
                'snap_token' => $snapToken,
                'order_id'   => $topup->midtrans_order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Deposit top-up checkout failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal memproses top-up: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Beralih ke plan DEPOSIT. Jika sedang berlangganan bulanan aktif, langganan HANGUS.
     */
    public function switchToDeposit(Request $request)
    {
        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        if ($tenant->isDepositMode()) {
            return redirect()->route('deposit.index')->with('info', 'Akun Anda sudah memakai plan deposit.');
        }

        $this->deposit->switchToDeposit($tenant);

        return redirect()->route('deposit.index')->with('success', 'Berhasil beralih ke plan deposit. Poin Anda kini aktif kembali. Langganan bulanan (jika ada) telah dihentikan.');
    }

    private function configureMidtrans(): void
    {
        \Midtrans\Config::$serverKey    = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;

        $notifyUrl = config('services.midtrans.notify_url');
        if (!empty($notifyUrl)) {
            \Midtrans\Config::$overrideNotifUrl = $notifyUrl;
        }
    }
}
