<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Tripay\Tripay;
use App\Support\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pilih SATU payment gateway aktif (midtrans | doku | tripay) — Superadmin.
 * Disimpan di tabel payment_settings (via App\Support\Billing).
 */
class PaymentGatewayController extends Controller
{
    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    public function index()
    {
        $this->guard();

        $drivers = [];
        foreach (Billing::DRIVERS as $key => $label) {
            $drivers[$key] = [
                'label'      => $label,
                'configured' => Billing::isConfigured($key),
            ];
        }

        $tripay = new Tripay();
        $tripayInfo = [
            'configured' => $tripay->isConfigured(),
            'production' => $tripay->isProduction(),
            'channels'   => \App\Models\TripayChannel::where('is_active', true)->count(),
        ];

        return view('backend.superadmin.payment-gateway.index', [
            'active'      => Billing::driver(),
            'drivers'     => $drivers,
            'tripayInfo'  => $tripayInfo,
            'envFallback' => (string) config('billing.driver', 'midtrans'),
        ]);
    }

    public function update(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'active_driver' => ['required', 'in:' . implode(',', array_keys(Billing::DRIVERS))],
        ]);

        $driver = $data['active_driver'];
        $label  = Billing::DRIVERS[$driver] ?? $driver;

        // Cegah mengaktifkan gateway yang kredensialnya belum lengkap (mencegah checkout gagal untuk tenant).
        if (! Billing::isConfigured($driver)) {
            return back()->with('error', 'Gateway ' . $label . ' belum dikonfigurasi (kredensial di .env belum lengkap). Aktivasi dibatalkan.');
        }

        Billing::setDriver($driver);

        return back()->with('success', 'Payment gateway aktif sekarang: ' . $label . '.');
    }
}
