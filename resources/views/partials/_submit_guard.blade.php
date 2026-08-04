{{--
  PENJAGA TOMBOL SIMPAN — satu tempat untuk seluruh aplikasi.

  Masalah nyata di lapangan: sinyal di gudang/kandang lambat, tombol Simpan
  terlihat "tidak bereaksi", lalu ditekan dua-tiga kali. Akibatnya data ganda —
  dua nota, dua setoran deposit, dua penyesuaian stok untuk satu kejadian.

  Yang dilakukan:
    1. Sekali kirim saja. Kiriman kedua dari form yang sama dibatalkan.
    2. Tombol berubah jadi spinner + teks "Menyimpan…" (atau "Menghapus…" untuk
       aksi hapus) supaya jelas prosesnya sedang jalan.
    3. Garis tipis berjalan di atas halaman, terlihat walau tombolnya sudah
       tergeser keluar layar di HP.

  Yang TIDAK diganggu:
    - Form yang dibatalkan handler lain (mis. `return confirm(...)` yang ditolak,
      atau validasi JS) — ditandai lewat event.defaultPrevented.
    - Form yang ditangani AJAX (memanggil preventDefault sendiri), termasuk form
      pencarian/filter method GET.
    - Form dengan atribut data-no-guard, bila suatu saat perlu dikecualikan.
--}}
<style>
    /* Garis kemajuan tipis: satu-satunya penanda yang tetap terlihat di HP
       saat halaman panjang dan tombolnya sudah tergeser jauh ke bawah. */
    #mooda-busy-bar {
        position: fixed; top: 0; left: 0; height: 3px; width: 0;
        background: linear-gradient(90deg, #50cd89, #009ef7);
        z-index: 20000; opacity: 0; transition: width .25s ease, opacity .25s ease;
        pointer-events: none;
    }
    #mooda-busy-bar.jalan { opacity: 1; width: 92%; transition: width 12s ease-out, opacity .2s; }

    /* Tombol yang sedang bekerja: tidak bisa diklik lagi & isyaratnya jelas. */
    .mooda-sibuk { pointer-events: none !important; opacity: .78; }
    .mooda-sibuk .spinner-border { vertical-align: -2px; }
</style>

<script>
(function () {
    'use strict';

    var bar = null;

    function garis(jalan) {
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'mooda-busy-bar';
            document.body.appendChild(bar);
        }
        if (jalan) {
            bar.classList.remove('jalan');
            void bar.offsetWidth;          // paksa ulang animasi
            bar.classList.add('jalan');
        } else {
            bar.classList.remove('jalan');
            bar.style.width = '0';
        }
    }

    // Kata kerja diambil dari maksud tombol: "Menghapus…" untuk aksi hapus terasa
    // jauh lebih tenang daripada "Menyimpan…" saat yang terjadi justru penghapusan.
    function kataKerja(form, tombol) {
        var teks = ((tombol && tombol.textContent) || '').toLowerCase();
        var metode = (form.querySelector('input[name="_method"]') || {}).value || form.method || '';
        metode = String(metode).toLowerCase();

        if (/hapus|delete|buang/.test(teks) || metode === 'delete') return 'Menghapus…';
        if (/batal/.test(teks)) return 'Membatalkan…';
        if (/cetak|unduh|download/.test(teks)) return 'Menyiapkan…';
        if (/cari|filter|tampilkan/.test(teks)) return 'Memuat…';
        return 'Menyimpan…';
    }

    function sibukkan(form) {
        if (!form || form.dataset.moodaSibuk === '1') return false;
        form.dataset.moodaSibuk = '1';

        var tombol = form.querySelectorAll(
            'button[type="submit"], button:not([type]), input[type="submit"]');

        tombol.forEach(function (b, i) {
            // Lebar dikunci lebih dulu supaya tata letak tidak melompat saat
            // teksnya berganti jadi lebih pendek/panjang.
            var r = b.getBoundingClientRect();
            if (r.width) b.style.minWidth = Math.ceil(r.width) + 'px';

            b.dataset.moodaIsiAsli = b.innerHTML;
            // Keadaan semula diingat: ada tombol yang memang sengaja dimatikan
            // halaman (mis. Simpan Realisasi saat belum ada selisih). Jaring
            // pengaman tidak boleh menghidupkannya.
            b.dataset.moodaSemulaMati = b.disabled ? '1' : '';
            b.classList.add('mooda-sibuk');
            b.setAttribute('aria-busy', 'true');

            // Tombol pertama yang membawa pesan; sisanya cukup dimatikan.
            if (i === 0) {
                var label = kataKerja(form, b);
                if (b.tagName === 'INPUT') {
                    b.dataset.moodaNilaiAsli = b.value;
                    b.value = label;
                } else {
                    b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"'
                        + ' aria-hidden="true"></span>' + label;
                }
            }
            // disabled dipasang SETELAH nilai dibaca; tombol disabled tidak ikut
            // terkirim, jadi tombol yang punya name+value tidak dimatikan.
            if (!b.name) b.disabled = true;
        });

        garis(true);

        return true;
    }

    function lepaskan(form) {
        if (!form || form.dataset.moodaSibuk !== '1') return;
        delete form.dataset.moodaSibuk;

        form.querySelectorAll('[data-mooda-isi-asli]').forEach(function (b) {
            if (b.tagName === 'INPUT' && b.dataset.moodaNilaiAsli !== undefined) {
                b.value = b.dataset.moodaNilaiAsli;
            } else {
                b.innerHTML = b.dataset.moodaIsiAsli;
            }
            b.disabled = b.dataset.moodaSemulaMati === '1';
            b.classList.remove('mooda-sibuk');
            b.removeAttribute('aria-busy');
            b.style.minWidth = '';
            delete b.dataset.moodaIsiAsli;
            delete b.dataset.moodaNilaiAsli;
            delete b.dataset.moodaSemulaMati;
        });

        garis(false);
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if ('noGuard' in form.dataset) return;

        // Sudah dibatalkan pihak lain (confirm ditolak / validasi JS / ditangani AJAX).
        if (e.defaultPrevented) return;

        // Kiriman kedua: hentikan di sini. Inilah inti penjagaan.
        if (form.dataset.moodaSibuk === '1') {
            e.preventDefault();
            e.stopImmediatePropagation();
            garis(true);

            return;
        }

        sibukkan(form);

        // Jaring pengaman: bila halaman tidak berpindah (mis. tanggapan berupa
        // unduhan berkas, atau permintaan gagal di tengah jalan), tombol dibuka
        // lagi supaya pengguna tidak terjebak pada layar yang seolah menggantung.
        setTimeout(function () { lepaskan(form); }, 25000);
    }, false);

    // form.submit() dari kode (mis. setelah konfirmasi SweetAlert) TIDAK memicu
    // peristiwa submit, jadi penjagaannya dipasang di sini juga.
    var submitAsli = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (!('noGuard' in this.dataset)) {
            if (this.dataset.moodaSibuk === '1') return;   // klik kedua: abaikan
            sibukkan(this);
        }

        return submitAsli.apply(this, arguments);
    };

    // Kembali lewat tombol Back peramban: halaman diambil dari cache dalam
    // keadaan "sedang menyimpan". Dibuka kembali supaya bisa dipakai lagi.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-mooda-sibuk="1"]').forEach(lepaskan);
    });

    // Dipakai kode lain (mis. alur SweetAlert + fetch) untuk memakai penjagaan
    // yang sama tanpa menyalin logikanya.
    window.MoodaSubmitGuard = { kunci: sibukkan, buka: lepaskan, garis: garis };
})();
</script>
