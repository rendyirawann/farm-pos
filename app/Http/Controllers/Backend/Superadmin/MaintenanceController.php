<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SiteOption;
use Illuminate\Http\Request;

/**
 * Pengaturan Mode Pemeliharaan (Superadmin).
 */
class MaintenanceController extends Controller
{
    public function index()
    {
        return view('backend.superadmin.maintenance.index', [
            'enabled' => SiteOption::get('maintenance_mode', '0') === '1',
            'message' => SiteOption::get('maintenance_message', ''),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        SiteOption::set('maintenance_mode', $request->boolean('enabled') ? '1' : '0');
        SiteOption::set('maintenance_message', trim($data['message'] ?? ''));

        return back()->with('success', $request->boolean('enabled')
            ? 'Mode Pemeliharaan DIAKTIFKAN. Semua pengguna (selain Superadmin) akan diarahkan keluar.'
            : 'Mode Pemeliharaan DIMATIKAN. Aplikasi kembali dapat diakses normal.');
    }
}
