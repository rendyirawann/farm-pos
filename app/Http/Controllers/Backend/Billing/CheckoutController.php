<?php

namespace App\Http\Controllers\Backend\Billing;

use App\Http\Controllers\Controller;
use App\Models\TripayChannel;
use App\Support\Billing;
use App\Tenancy\DepositConfig;
use App\Tenancy\Plan;
use Illuminate\Http\Request;

/**
 * Halaman checkout pembayaran (gaya /checkout-demo) untuk Langganan & Top-up Deposit.
 * Menampilkan ringkasan (plan+durasi / nominal deposit) + kartu metode; pembayaran
 * dibuat via endpoint billing.checkout / deposit.checkout, lalu VA/QRIS tampil in-app.
 */
class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $type = (string) $request->query('type');
        $channels = TripayChannel::activeOrdered();

        if ($type === 'deposit') {
            $amount = (int) $request->query('amount', 0);
            $points = DepositConfig::pointsForTopup($amount);
            abort_if($amount <= 0 || $points === null, 404);
            $summary = [
                'type'     => 'deposit',
                'item'     => 'Top-up Saldo Deposit',
                'item_tag' => 'Paket',
                'note'     => 'Saldo diterima: ' . number_format($points, 0, ',', '.') . ' (Rp1 = 1 saldo)',
                'purpose'  => 'Saldo aplikasi Mooda',
                'amount'   => $amount,
                'back'     => route('deposit.index'),
                'endpoint' => route('deposit.checkout'),
                'payload'  => ['amount' => $amount],
            ];
        } elseif ($type === 'subscription') {
            $plan = (string) $request->query('plan', '');
            $months = max(1, (int) $request->query('months', 1));
            abort_if(! array_key_exists($plan, Plan::all()) || Plan::isContact($plan), 404);
            $amount = Plan::periodAmount($plan, $months);
            abort_if($amount === null, 404);
            $summary = [
                'type'     => 'subscription',
                'item'     => 'Langganan ' . Plan::name($plan),
                'item_tag' => $months . ' bulan',
                'note'     => 'Rp' . number_format((int) round($amount / max(1, $months)), 0, ',', '.') . ' / bulan',
                'purpose'  => 'Langganan aplikasi Mooda',
                'amount'   => (int) $amount,
                'back'     => route('billing.index'),
                'endpoint' => route('billing.checkout'),
                'payload'  => ['plan' => $plan, 'months' => $months],
            ];
        } else {
            abort(404);
        }

        return view('backend.billing.checkout', [
            'summary'  => $summary,
            'channels' => $channels,
            'driver'   => Billing::driver(),
        ]);
    }
}
