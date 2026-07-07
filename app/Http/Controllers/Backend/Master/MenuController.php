<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuAddon;
use App\Models\Category;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        return view('backend.master.menus.index', compact('categories'));
    }

    public function getDataMenus(Request $request)
    {
        if ($request->ajax()) {
            $data = Menu::with('category')->orderBy('created_at', 'desc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image_view', function ($row) {
                    $imgUrl = $row->image ? asset('storage/menus/' . $row->image) : asset('assets/media/svg/files/blank-image.svg');
                    return '<div class="symbol symbol-50px"><img src="' . $imgUrl . '" alt="foto" style="object-fit:cover;"/></div>';
                })
                ->addColumn('menu_info', function ($row) {
                    $category = $row->category ? $row->category->name : 'Tanpa Kategori';
                    return '<span class="fw-bold text-gray-800">' . $row->name . '</span><br><span class="badge badge-light-primary fs-8 mt-1">' . $category . '</span>';
                })
                ->addColumn('price_format', function ($row) {
                    // Cek jika ada diskon khusus menu ini
                    if ($row->discount_percent > 0) {
                        $discountedPrice = $row->price - ($row->price * ($row->discount_percent / 100));
                        return '<span class="text-muted text-decoration-line-through fs-8">Rp ' . number_format($row->price, 0, ',', '.') . '</span><br>' .
                            '<span class="text-success fw-bold">Rp ' . number_format($discountedPrice, 0, ',', '.') . '</span> ' .
                            '<span class="badge badge-light-danger fs-9">-' . $row->discount_percent . '%</span>';
                    }
                    return '<span class="text-success fw-bold">Rp ' . number_format($row->price, 0, ',', '.') . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    if ($row->is_available) {
                        return '<span class="badge badge-light-success fs-7"><i class="ki-outline ki-check fs-5 text-success me-1"></i> Tersedia</span>';
                    } else {
                        return '<span class="badge badge-light-danger fs-7"><i class="ki-outline ki-cross fs-5 text-danger me-1"></i> Habis</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="btn btn-sm btn-icon btn-light-info btn-detail me-2" data-id="' . $row->id . '" title="Detail"><i class="ki-outline ki-eye fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-primary btn-edit me-2" data-id="' . $row->id . '" title="Edit"><i class="ki-outline ki-pencil fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . $row->name . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>';
                    return $btn;
                })
                ->rawColumns(['image_view', 'menu_info', 'price_format', 'status_badge', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100', // <-- TAMBAHKAN BARIS INI
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description'      => 'nullable|string'
        ]);

        $data = $request->except(['image', 'addon_name', 'addon_price']);
        $data['is_available'] = $request->has('is_available') ? true : false;

        // Proses Upload Image
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'menu-' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('menus', $file, $filename);
            $data['image'] = $filename;
        }

        $menu = Menu::create($data);
        $this->syncAddons($menu, $request);

        return response()->json(['success' => 'Menu berhasil ditambahkan!']);
    }

    public function show($id)
    {
        $menu = Menu::with(['category', 'addons'])->findOrFail($id);
        $html = view('backend.master.menus.show', compact('menu'))->render();
        return response()->json(['html' => $html]);
    }

    public function edit($id)
    {
        $menu = Menu::with('addons')->findOrFail($id);
        $categories = Category::orderBy('name', 'asc')->get();
        $html = view('backend.master.menus.edit', compact('menu', 'categories'))->render();
        return response()->json(['html' => $html]);
    }

    /** JSON add-ons aktif untuk sebuah menu (dipakai kasir / form). */
    public function getAddons($id)
    {
        $menu = Menu::with('activeAddons')->findOrFail($id);
        return response()->json([
            'addons' => $menu->activeAddons->map(fn($a) => [
                'id'    => $a->id,
                'name'  => $a->name,
                'price' => (float) $a->price,
            ]),
        ]);
    }

    /** Simpan ulang add-ons dari input form (addon_name[] & addon_price[]). */
    private function syncAddons(Menu $menu, Request $request): void
    {
        $names  = (array) $request->input('addon_name', []);
        $prices = (array) $request->input('addon_price', []);

        // Reset lalu buat ulang (order_details menyimpan snapshot, jadi aman menghapus).
        $menu->addons()->delete();

        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $menu->addons()->create([
                'name'      => $name,
                'price'     => (float) ($prices[$i] ?? 0),
                'is_active' => true,
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100', // <-- TAMBAHKAN BARIS INI
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description'      => 'nullable|string'
        ]);

        $menu = Menu::findOrFail($id);
        $data = $request->except(['image', 'addon_name', 'addon_price']);
        $data['is_available'] = $request->has('is_available') ? true : false;

        // Proses Upload Image Baru jika ada
        if ($request->hasFile('image')) {
            // Hapus gambar lama
            if ($menu->image && Storage::disk('public')->exists('menus/' . $menu->image)) {
                Storage::disk('public')->delete('menus/' . $menu->image);
            }
            $file = $request->file('image');
            $filename = 'menu-' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('menus', $file, $filename);
            $data['image'] = $filename;
        }

        $menu->update($data);
        $this->syncAddons($menu, $request);

        return response()->json(['success' => 'Menu berhasil diupdate!']);
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        // Hapus file gambar jika ada
        if ($menu->image && Storage::disk('public')->exists('menus/' . $menu->image)) {
            Storage::disk('public')->delete('menus/' . $menu->image);
        }
        $menu->delete();
        return response()->json(['success' => 'Menu berhasil dihapus!']);
    }

    // ============================================================
    // IMPORT MENU VIA CSV
    // ============================================================

    /** Unduh template CSV agar owner/admin tinggal mengisi lalu meng-upload. */
    public function downloadTemplate()
    {
        abort_unless(auth()->user()->can('menu.create'), 403);

        $rows = [
            ['nama', 'harga', 'kategori', 'deskripsi', 'tersedia'],
            ['Kopi Susu Gula Aren', '18000', 'Beverages', 'Signature house blend', 'Ya'],
            ['Nasi Goreng Spesial', '25000', 'Main Course', 'Nasi goreng spesial pakai telur', 'Ya'],
            ['Es Teh Manis', '8000', '', 'Kosongkan kategori = terdeteksi otomatis', 'Ya'],
        ];

        // Kutip HANYA bila perlu (ada koma/kutip/newline) supaya terlihat bersih bagi user awam.
        $cell = function ($v) {
            $v = (string) $v;
            return preg_match('/[",\r\n]/', $v) ? '"' . str_replace('"', '""', $v) . '"' : $v;
        };

        // BOM UTF-8 + baris "sep=," = petunjuk agar Excel (semua locale) langsung membagi ke kolom.
        $csv = "\xEF\xBB\xBF" . "sep=,\r\n";
        foreach ($rows as $r) {
            $csv .= implode(',', array_map($cell, $r)) . "\r\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-menu-mooda.csv"',
        ]);
    }

    /** Import massal menu dari file CSV yang di-upload. */
    public function importCsv(Request $request)
    {
        abort_unless(auth()->user()->can('menu.create'), 403);

        $request->validate(['file' => 'required|file|max:4096']);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->with('import_error', 'File harus berformat .csv. Unduh template untuk contoh yang benar.');
        }

        $content = (string) file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // buang BOM
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        // Lewati baris petunjuk Excel "sep=," bila ada (baris pertama template).
        $headerIdx = (isset($lines[0]) && stripos(trim($lines[0]), 'sep=') === 0) ? 1 : 0;
        if (!$lines || count($lines) < $headerIdx + 2) {
            return back()->with('import_error', 'File CSV kosong atau tidak ada baris data.');
        }

        // Deteksi pemisah dari baris header (koma atau titik-koma — Excel Indonesia sering pakai ";").
        $headerLine = $lines[$headerIdx];
        $delim = (substr_count($headerLine, ';') > substr_count($headerLine, ',')) ? ';' : ',';

        // Petakan kolom dari header (fleksibel: dukung alias Indonesia).
        $map = [];
        foreach (str_getcsv($headerLine, $delim) as $i => $h) {
            $key = strtolower(trim($h));
            if (in_array($key, ['name', 'nama', 'menu', 'nama menu'], true)) $map['name'] = $i;
            elseif (in_array($key, ['price', 'harga'], true)) $map['price'] = $i;
            elseif (in_array($key, ['category', 'kategori', 'kategory'], true)) $map['category'] = $i;
            elseif (in_array($key, ['description', 'deskripsi', 'desc', 'keterangan'], true)) $map['description'] = $i;
            elseif (in_array($key, ['available', 'tersedia', 'status', 'aktif'], true)) $map['available'] = $i;
        }

        if (!isset($map['name']) || !isset($map['price'])) {
            return back()->with('import_error', 'Header CSV wajib memuat kolom "name" dan "price". Silakan unduh & pakai template.');
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $maxRows = 1000;

        for ($ln = $headerIdx + 1; $ln < count($lines); $ln++) {
            if ($ln > $maxRows) {
                $errors[] = "Dihentikan di baris $maxRows (batas maksimum per import).";
                break;
            }
            $raw = trim($lines[$ln]);
            if ($raw === '') continue;

            $cols = str_getcsv($raw, $delim);
            $name = trim($cols[$map['name']] ?? '');
            if ($name === '') continue;

            $price = $this->parsePrice($cols[$map['price']] ?? '');
            if ($price === null) {
                $skipped++;
                $errors[] = "Baris " . ($ln + 1) . ": harga tidak valid untuk '$name', dilewati.";
                continue;
            }

            // Hindari duplikat: lewati bila nama menu sudah ada (ter-scope per tenant).
            if (Menu::where('name', $name)->exists()) {
                $skipped++;
                $errors[] = "Baris " . ($ln + 1) . ": menu '$name' sudah ada, dilewati.";
                continue;
            }

            $catName = isset($map['category']) ? trim($cols[$map['category']] ?? '') : '';
            if ($catName === '') {
                $catName = $this->detectCategory($name);
            }
            $category = $this->findOrCreateCategory($catName);

            $available = isset($map['available']) ? $this->parseBool($cols[$map['available']] ?? '1') : true;
            $desc = isset($map['description']) ? trim($cols[$map['description']] ?? '') : '';

            try {
                Menu::create([
                    'category_id'  => $category->id,
                    'name'         => $name,
                    'description'  => $desc !== '' ? $desc : null,
                    'price'        => $price,
                    'is_available' => $available,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Baris " . ($ln + 1) . ": gagal menyimpan '$name'.";
            }
        }

        if ($created === 0 && $skipped === 0) {
            return back()->with('import_error', 'Tidak ada baris data yang terbaca. Periksa isi file CSV.');
        }

        $summary = "$created menu berhasil ditambahkan" . ($skipped > 0 ? ", $skipped baris dilewati." : ".");
        return back()->with('import_summary', $summary)->with('import_errors', array_slice($errors, 0, 25));
    }

    /** "Rp 18.000" / "18000" / "18,000" -> 18000 (integer). Null bila tak ada angka. */
    private function parsePrice($raw): ?int
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $raw);
        return $digits === '' ? null : (int) $digits;
    }

    /** Nilai kosong dianggap tersedia; hanya nilai falsy eksplisit -> false. */
    private function parseBool($raw): bool
    {
        $v = strtolower(trim((string) $raw));
        return !in_array($v, ['0', 'no', 'tidak', 'habis', 'false', 'n', 'off', 'nonaktif'], true);
    }

    /** Tebak kategori dari nama menu: minuman (Beverages) vs makanan (Main Course). */
    private function detectCategory(string $name): string
    {
        $n = strtolower($name);
        $beverages = [
            'kopi', 'coffee', 'teh', ' tea', 'es ', 'ice', 'juice', 'jus', 'susu', 'milk', 'latte',
            'americano', 'cappu', 'espresso', 'macchiato', 'mocha', 'matcha', 'soda', 'cola', 'coke',
            'sprite', 'fanta', 'air ', 'mineral', 'lemon', 'lime', 'mojito', 'mocktail', 'smoothie',
            'frappe', 'frapp', 'boba', 'shake', 'wedang', 'jahe', 'coklat', 'cokelat', 'chocolate',
            'choco', 'yakult', 'float', 'squash', 'sparkling', 'tonic', 'minuman', 'drink', 'beer', 'bir',
        ];
        foreach ($beverages as $kw) {
            if (strpos($n, $kw) !== false) return 'Beverages';
        }
        return 'Main Course';
    }

    /** Cari kategori (case-insensitive, per tenant) atau buat baru bila belum ada. */
    private function findOrCreateCategory(string $name): Category
    {
        $name = trim($name) ?: 'Lainnya';
        $existing = Category::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) return $existing;

        return Category::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
        ]);
    }
}
