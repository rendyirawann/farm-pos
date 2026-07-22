<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\TripayChannel;
use App\Services\Tripay\Tripay;
use App\Support\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola channel pembayaran Tripay (Superadmin). Kode channel di-copy dari dashboard Tripay
 * (Channel Pembayaran). Customer memilih dari channel yang aktif saat checkout.
 */
class TripayChannelController extends Controller
{
    /** Saran kode channel Tripay yang umum (untuk datalist input). */
    private const SUGGESTED = [
        'QRIS'         => 'QRIS',
        'QRISC'        => 'QRIS (Customizable)',
        'BRIVA'        => 'BRI Virtual Account',
        'BNIVA'        => 'BNI Virtual Account',
        'MANDIRIVA'    => 'Mandiri Virtual Account',
        'BCAVA'        => 'BCA Virtual Account',
        'PERMATAVA'    => 'Permata Virtual Account',
        'BSIVA'        => 'BSI Virtual Account',
        'CIMBVA'       => 'CIMB Niaga Virtual Account',
        'MUAMALATVA'   => 'Muamalat Virtual Account',
        'DANAMONVA'    => 'Danamon Virtual Account',
        'BNCVA'        => 'Neo Commerce Virtual Account',
        'SMSVA'        => 'Sinarmas Virtual Account',
        'OCBCVA'       => 'OCBC NISP Virtual Account',
        'OTHERBANKVA'  => 'Bank Lain (Virtual Account)',
        'ALFAMART'     => 'Alfamart',
        'INDOMARET'    => 'Indomaret',
        'ALFAMIDI'     => 'Alfamidi',
        'OVO'          => 'OVO',
        'DANA'         => 'DANA',
        'SHOPEEPAY'    => 'ShopeePay',
    ];

    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    /**
     * Sinkron channel dari API Tripay: aktifkan channel yang AKTIF di Tripay,
     * nonaktifkan sisanya. Mencegah "channel not enabled" karena mismatch.
     */
    public function sync()
    {
        $this->guard();
        $tripay = new Tripay();
        if (! $tripay->isConfigured()) {
            return back()->with('error', 'Tripay belum dikonfigurasi (cek kredensial di .env).');
        }
        $channels = $tripay->paymentChannels(true); // hanya channel AKTIF di Tripay
        if (empty($channels)) {
            return back()->with('error', 'Tidak ada channel aktif dari Tripay. Cek merchant/kredensial atau aktifkan channel di dashboard Tripay.');
        }
        $codes = [];
        foreach ($channels as $i => $c) {
            $code = (string) ($c['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $codes[] = $code;
            $ch = TripayChannel::firstOrNew(['code' => $code]);
            $ch->name = $c['name'] ?: $code;
            $ch->group = $c['group'] ?? '';
            $ch->is_active = true;
            if (! $ch->exists) {
                $ch->sort_order = $i + 1; // urutan manual dipertahankan saat re-sync
            }
            $ch->save();
        }
        $off = TripayChannel::whereNotIn('code', $codes)->update(['is_active' => false]);

        return back()->with('success', 'Sinkron selesai: ' . count($codes) . ' channel aktif dari Tripay disimpan, ' . $off . ' channel lain dinonaktifkan.');
    }

    public function index()
    {
        $this->guard();

        return view('backend.superadmin.tripay-channels.index', [
            'channels'  => TripayChannel::orderBy('sort_order')->orderBy('name')->get(),
            'suggested' => self::SUGGESTED,
            'driver'    => Billing::driver(),
            'production' => (bool) config('services.tripay.is_production', false),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request);
        TripayChannel::create($data);
        return back()->with('success', 'Channel ' . $data['name'] . ' ditambahkan.');
    }

    public function update(Request $request, TripayChannel $channel)
    {
        $this->guard();
        $channel->update($this->validated($request, $channel->id));
        return back()->with('success', 'Channel ' . $channel->name . ' diperbarui.');
    }

    public function toggle(TripayChannel $channel)
    {
        $this->guard();
        $channel->update(['is_active' => ! $channel->is_active]);
        return back()->with('success', 'Status ' . $channel->name . ' diubah.');
    }

    public function destroy(TripayChannel $channel)
    {
        $this->guard();
        $name = $channel->name;
        $channel->delete();
        return back()->with('success', 'Channel ' . $name . ' dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_]+$/', 'unique:tripay_channels,code' . ($ignoreId ? ',' . $ignoreId : '')],
            'group'      => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.regex' => 'Kode channel harus HURUF KAPITAL/angka (mis. QRIS, BRIVA) tanpa spasi.',
            'code.unique' => 'Kode channel sudah ada.',
        ]) + ['is_active' => (bool) $request->boolean('is_active', true)];
    }
}
