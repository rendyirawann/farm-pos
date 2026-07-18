<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DokuVaChannel;
use App\Services\Doku\DokuSnap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola channel Virtual Account DOKU (SNAP) — Superadmin.
 * Nilai per bank di-copy dari dashboard DOKU (Payment Virtual Account -> Configure).
 */
class DokuChannelController extends Controller
{
    /** Daftar channel VaChannels DOKU yang valid (untuk dropdown). */
    private const CHANNELS = [
        'VIRTUAL_ACCOUNT_BCA'          => 'BCA',
        'VIRTUAL_ACCOUNT_BANK_MANDIRI' => 'Mandiri',
        'VIRTUAL_ACCOUNT_BRI'          => 'BRI',
        'VIRTUAL_ACCOUNT_BNI'          => 'BNI',
        'VIRTUAL_ACCOUNT_BSI'          => 'BSI',
        'VIRTUAL_ACCOUNT_BANK_CIMB'    => 'CIMB Niaga',
        'VIRTUAL_ACCOUNT_BANK_PERMATA' => 'Permata',
        'VIRTUAL_ACCOUNT_BANK_DANAMON' => 'Danamon',
        'VIRTUAL_ACCOUNT_MAYBANK'      => 'Maybank',
        'VIRTUAL_ACCOUNT_BTN'          => 'BTN',
        'VIRTUAL_ACCOUNT_BNC'          => 'Neo Commerce',
        'VIRTUAL_ACCOUNT_SINARMAS'     => 'Sinarmas',
        'VIRTUAL_ACCOUNT_BSS'          => 'Sahabat Sampoerna',
        'VIRTUAL_ACCOUNT_DOKU'         => 'DOKU (multi-bank)',
    ];

    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    public function index()
    {
        $this->guard();
        $env = config('services.doku.is_production') ? 'production' : 'sandbox';
        $channels = DokuVaChannel::orderBy('environment')->orderBy('sort_order')->orderBy('name')->get();

        return view('backend.superadmin.doku-channels.index', [
            'channels'    => $channels,
            'options'     => self::CHANNELS,
            'currentEnv'  => $env,
            'driver'      => config('billing.driver'),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request);
        DokuVaChannel::create($data);
        return back()->with('success', 'Channel ' . $data['name'] . ' ditambahkan.');
    }

    public function update(Request $request, DokuVaChannel $channel)
    {
        $this->guard();
        $channel->update($this->validated($request, $channel->id));
        return back()->with('success', 'Channel ' . $channel->name . ' diperbarui.');
    }

    public function toggle(DokuVaChannel $channel)
    {
        $this->guard();
        $channel->update(['is_active' => ! $channel->is_active]);
        return back()->with('success', 'Status ' . $channel->name . ' diubah.');
    }

    public function destroy(DokuVaChannel $channel)
    {
        $this->guard();
        $name = $channel->name;
        $channel->delete();
        return back()->with('success', 'Channel ' . $name . ' dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $env = $request->input('environment', config('services.doku.is_production') ? 'production' : 'sandbox');

        return $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'channel'            => ['required', 'string', 'in:' . implode(',', array_keys(self::CHANNELS))],
            'partner_service_id' => ['required', 'string', 'regex:/^\d{1,8}$/'], // Merchant BIN, digit
            'prefix_customer'    => ['nullable', 'string', 'max:5'],
            'environment'        => ['required', 'in:sandbox,production'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
        ], [
            'partner_service_id.regex' => 'Partner Service ID / Merchant BIN harus angka (maks 8 digit).',
        ]) + ['is_active' => (bool) $request->boolean('is_active', true)];
    }
}
