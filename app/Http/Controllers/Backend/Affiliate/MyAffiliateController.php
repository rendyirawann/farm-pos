<?php

namespace App\Http\Controllers\Backend\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Modul AFFILIATE — sisi TENANT (owner pelanggan POS) di dalam mooda.id/admin.
 * Owner bisa GABUNG program & melihat dashboard referral-nya sendiri.
 * Pakai akun POS yang sama (tanpa akun terpisah). Gate: can:affiliate.refer (owner).
 */
class MyAffiliateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $affiliate = Affiliate::where('user_id', $user->id)->first()
            ?? Affiliate::where('type', 'tenant')->where('tenant_id', $user->tenant_id)->first();

        $referrals = collect();
        $stats = ['total' => 0, 'subscribed' => 0, 'earned' => 0.0, 'pending' => 0.0];

        if ($affiliate) {
            $referrals = $affiliate->referrals()->with('tenant')->orderByDesc('created_at')->get();
            $stats = [
                'total'      => $referrals->count(),
                'subscribed' => $referrals->where('status', 'subscribed')->count(),
                'earned'     => (float) $referrals->where('commission_status', 'paid')->sum('commission_amount'),
                'pending'    => (float) $referrals->where('commission_status', '!=', 'paid')->sum('commission_amount'),
            ];
        }

        $komisi = config('affiliate.commission_type') === 'percent'
            ? number_format((float) config('affiliate.commission_value'), 0, ',', '.') . '% dari langganan'
            : 'Rp ' . number_format((float) config('affiliate.commission_value'), 0, ',', '.') . ' / referral';

        return view('backend.affiliate.my', compact('affiliate', 'referrals', 'stats', 'komisi'));
    }

    public function join(Request $request)
    {
        $user = Auth::user();

        if (Affiliate::where('user_id', $user->id)->orWhere(fn ($q) => $q->where('type', 'tenant')->where('tenant_id', $user->tenant_id))->exists()) {
            return redirect()->route('affiliate.my')->with('warning', 'Anda sudah terdaftar sebagai afiliator.');
        }

        $tenant = $user->tenant;
        $aff = Affiliate::create([
            'code'      => Affiliate::generateCode($tenant->name ?? $user->name),
            'name'      => $tenant->name ?? $user->name,
            'email'     => $user->email,
            'phone'     => $user->no_wa ?? $user->phone,
            'type'      => 'tenant',
            'tenant_id' => $user->tenant_id,
            'user_id'   => $user->id,
            'status'    => 'active', // pelanggan POS terverifikasi -> langsung aktif
        ]);

        activity()->useLog('affiliate')->causedBy($user)->performedOn($aff)
            ->log('Owner gabung affiliate: ' . $aff->code);

        return redirect()->route('affiliate.my')
            ->with('success', 'Selamat! Anda kini afiliator Mooda. Bagikan link referral Anda & dapatkan komisi.');
    }
}
