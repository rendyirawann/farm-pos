<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    // Menampilkan form pengaturan (karena cuma 1 baris, kita buat otomatis jika kosong)
    public function index()
    {
        $setting = Setting::first();

        // Jika belum ada data sama sekali, buat 1 baris default
        if (!$setting) {
            $setting = Setting::create([
                'store_name'     => 'Stakko POS',
                'tax_rate'       => 10,
                'printer_method' => 'auto',
                'paper_width'    => 58,
            ]);
        }

        return view('backend.settings.index', compact('setting'));
    }

    // Menyimpan perubahan pengaturan
    public function update(Request $request)
    {
        // Pengaturan printer (tab Printer) boleh diubah semua role.
        $rules = [
            'printer_method' => 'nullable|in:auto,browser,qztray,webbluetooth,rawbt',
            'paper_width'    => 'nullable|in:58,80',
        ];
        $fields = ['printer_method', 'paper_width'];

        // Pengaturan toko/pajak (tab Umum) hanya owner/admin/Superadmin.
        $canGeneral = auth()->user()->can('view_data_master');
        if ($canGeneral) {
            $rules += [
                'store_name' => 'required|string|max:255',
                'address'    => 'nullable|string|max:500',
                'phone'      => 'nullable|string|max:30',
                'tax_rate'   => 'required|numeric|min:0|max:100',
            ];
            $fields = array_merge($fields, ['store_name', 'address', 'phone', 'tax_rate']);
        }

        $request->validate($rules);

        $setting = Setting::first();
        $setting->update($request->only($fields));

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui!');
    }
}
