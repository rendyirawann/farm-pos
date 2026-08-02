{{-- Mesin cetak + penerjemah nota peternakan + tombol koneksi printer. --}}
{{-- mooda-print.js sudah dimuat layout (backend/layout/app.blade.php) — jangan dimuat ulang. --}}
<script src="{{ asset('assets/js/farm-nota.js') }}"></script>
<script>
  window.FARM_STORE_NAME = @json(optional($currentTenant)->name ?? 'Mooda Stok');

  (function () {
      var btn = document.getElementById('btn-printer');
      if (!btn || !window.MoodaPrint) return;

      if (window.MoodaPrint.needsButton()) {
          btn.classList.remove('d-none');
          document.getElementById('printer-label').textContent = window.MoodaPrint.buttonLabel();
          // Pulihkan printer BT yang sudah pernah diizinkan, tanpa dialog pemilihan.
          if (window.MoodaPrint.restoreBle) window.MoodaPrint.restoreBle();
      }
      btn.addEventListener('click', function () { window.MoodaPrint.quickConnect(); });
  })();
</script>
