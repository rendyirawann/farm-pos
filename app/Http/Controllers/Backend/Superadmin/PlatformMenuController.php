<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;

/**
 * "Platform Menu" — semua menu Superadmin dalam bentuk grid kartu.
 * Solusi agar menu tetap terjangkau saat sidebar/navbar terpotong di layar kecil
 * atau skala tampilan besar. Kartu ber-sub-menu membuka pop-up kartu.
 */
class PlatformMenuController extends Controller
{
    public function index()
    {
        // Struktur: group => kartu. Kartu punya 'route' (langsung) ATAU 'items' (pop-up).
        $groups = [
            [
                'title' => 'Operasional Platform',
                'cards' => [
                    ['label' => 'Manajemen Tenant', 'icon' => 'ki-abstract-26', 'color' => 'primary',
                     'route' => 'tenants.index', 'can' => 'view_tenants', 'desc' => 'Kelola semua tenant/toko'],
                    ['label' => 'Akun Demo', 'icon' => 'ki-rocket', 'color' => 'info',
                     'route' => 'demo-accounts.index', 'can' => 'view_tenants', 'desc' => 'Generate akun demo + deposit'],
                    ['label' => 'Dashboard Analitik', 'icon' => 'ki-chart-simple', 'color' => 'success',
                     'route' => 'dashboard', 'desc' => 'Ringkasan platform'],
                ],
            ],
            [
                'title' => 'Paket & Pembayaran',
                'cards' => [
                    ['label' => 'Setelan Paket', 'icon' => 'ki-price-tag', 'color' => 'info',
                     'route' => 'plan-settings.index', 'can' => 'view_tenants', 'desc' => 'Harga, diskon & promo paket'],
                    ['label' => 'Payment', 'icon' => 'ki-credit-cart', 'color' => 'warning', 'can' => 'view_tenants',
                     'desc' => 'Gateway, deposit & channel', 'items' => [
                        ['label' => 'Payment Gateway', 'route' => 'payment-gateway.index', 'icon' => 'ki-credit-cart'],
                        ['label' => 'Setelan Deposit', 'route' => 'deposit-settings.index', 'icon' => 'ki-wallet'],
                        ['label' => 'Channel VA DOKU', 'route' => 'doku-channels.index', 'icon' => 'ki-bank'],
                        ['label' => 'Channel Tripay', 'route' => 'tripay-channels.index', 'icon' => 'ki-bank'],
                     ]],
                ],
            ],
            [
                'title' => 'Affiliate',
                'cards' => [
                    ['label' => 'Affiliate', 'icon' => 'ki-share', 'color' => 'success', 'can' => 'affiliate.manage',
                     'desc' => 'Afiliator, pencairan & setelan', 'items' => [
                        ['label' => 'Daftar Affiliate', 'route' => 'affiliates.index', 'icon' => 'ki-share'],
                        ['label' => 'Pencairan Komisi', 'route' => 'affiliates.withdrawals', 'icon' => 'ki-dollar'],
                        ['label' => 'Setelan Affiliate', 'route' => 'affiliates.settings', 'icon' => 'ki-setting-3'],
                     ]],
                ],
            ],
            [
                'title' => 'Situs & Konten',
                'cards' => [
                    ['label' => 'Situs', 'icon' => 'ki-global', 'color' => 'primary', 'can' => 'blog.manage',
                     'desc' => 'Landing, FAQ, sosmed, partner', 'items' => [
                        ['label' => 'Kelola Situs', 'route' => 'site-content.index', 'icon' => 'ki-global'],
                        ['label' => 'FAQ Landing', 'route' => 'faqs.index', 'icon' => 'ki-question'],
                        ['label' => 'Sosial Media', 'route' => 'social-links.index', 'icon' => 'ki-share'],
                        ['label' => 'Logo Partner', 'route' => 'partner-logos.index', 'icon' => 'ki-picture'],
                        ['label' => 'Mode Pemeliharaan', 'route' => 'maintenance-settings.index', 'icon' => 'ki-shield-tick'],
                     ]],
                    ['label' => 'Blog', 'icon' => 'ki-notepad-edit', 'color' => 'info', 'can' => 'blog.manage',
                     'desc' => 'Artikel & kategori blog', 'items' => [
                        ['label' => 'Kelola Blog', 'route' => 'blog.admin.posts.index', 'icon' => 'ki-notepad-edit'],
                        ['label' => 'Kategori Blog', 'route' => 'blog.admin.categories.index', 'icon' => 'ki-category'],
                     ]],
                    ['label' => 'Tentang Kami (Founder)', 'icon' => 'ki-people', 'color' => 'success',
                     'route' => 'founders.index', 'can' => 'view_tenants', 'desc' => 'Profil & foto founder'],
                ],
            ],
            [
                'title' => 'Pengguna & Sistem',
                'cards' => [
                    ['label' => 'User Management', 'icon' => 'ki-profile-user', 'color' => 'primary',
                     'route' => 'users.index', 'can' => 'view_resources', 'desc' => 'Kelola user & akses'],
                    ['label' => 'Role Management', 'icon' => 'ki-key', 'color' => 'warning',
                     'route' => 'roles.index', 'can' => 'view_resources', 'desc' => 'Peran & permission'],
                    ['label' => 'Log Activity', 'icon' => 'ki-time', 'color' => 'info',
                     'route' => 'log-activity.index', 'can' => 'view_help', 'desc' => 'Riwayat aktivitas sistem'],
                    ['label' => 'Pengaturan', 'icon' => 'ki-setting-2', 'color' => 'success',
                     'route' => 'settings.index', 'desc' => 'Setelan aplikasi'],
                ],
            ],
        ];

        return view('backend.superadmin.platform-menu.index', compact('groups'));
    }
}
