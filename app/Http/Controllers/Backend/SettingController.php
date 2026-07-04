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
                'store_name'     => 'Mooda',
                'tax_rate'       => 10,
                'printer_method' => 'auto',
                'paper_width'    => 58,
                'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
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
        $data = $request->only(['printer_method', 'paper_width']);

        // Pengaturan toko/pajak + kustomisasi struk (tab Umum & Struk) hanya owner/admin/Superadmin.
        $canGeneral = auth()->user()->can('view_data_master');
        if ($canGeneral) {
            $rules += [
                'store_name'     => 'required|string|max:255',
                'address'        => 'nullable|string|max:500',
                'phone'          => 'nullable|string|max:30',
                'tax_rate'       => 'required|numeric|min:0|max:100',
                'receipt_header' => 'nullable|string|max:120',
                'receipt_footer' => 'nullable|string|max:255',
            ];
            $data = array_merge($data, $request->only([
                'store_name', 'address', 'phone', 'tax_rate', 'receipt_header', 'receipt_footer',
            ]));
            // Checkbox: tidak terkirim saat tidak dicentang -> tangani eksplisit.
            $data['receipt_show_address'] = $request->boolean('receipt_show_address');
            $data['receipt_show_phone']   = $request->boolean('receipt_show_phone');
        }

        $request->validate($rules);

        // Pastikan baris ada (mirror index) agar POST tanpa GET sebelumnya tidak fatal.
        $setting = Setting::firstOrCreate([], [
            'store_name'     => 'Mooda',
            'tax_rate'       => 10,
            'printer_method' => 'auto',
            'paper_width'    => 58,
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
        ]);
        $setting->update($data);

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui!');
    }
}
