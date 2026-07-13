// Landing page: layout VERTIKAL responsif untuk SEMUA perangkat (desktop, tablet, HP).
// Tidak lagi berupa slide horizontal (Swiper full-page) di desktop. Hanya burger menu,
// tombol "kembali ke atas", dan penanda mode .lp-mobile (yang mengaktifkan CSS layout vertikal).
// Anchor (#fitur/#galeri/#harga) memakai smooth-scroll native; kartu harga jadi grid/tumpukan via CSS.

function initLanding() {
    const el = document.querySelector('#landing-swiper');
    if (!el) return;

    // Aktifkan layout vertikal responsif di semua ukuran layar.
    document.documentElement.classList.add('lp-mobile');

    // ===== BURGER MENU =====
    const burger = document.getElementById('lp-burger');
    const mobileMenu = document.getElementById('lp-mobile-menu');
    if (burger && mobileMenu) {
        const closeMenu = () => { mobileMenu.classList.add('hidden'); burger.setAttribute('aria-expanded', 'false'); };
        burger.addEventListener('click', (e) => {
            e.stopPropagation();
            const opened = mobileMenu.classList.toggle('hidden') === false;
            burger.setAttribute('aria-expanded', opened ? 'true' : 'false');
        });
        mobileMenu.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));
        document.addEventListener('click', (e) => {
            if (!mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && !burger.contains(e.target)) closeMenu();
        });
    }

    // ===== TOMBOL KEMBALI KE ATAS =====
    const toTop = document.getElementById('lp-totop');
    if (toTop) {
        toTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}

if (document.readyState !== 'loading') {
    initLanding();
} else {
    document.addEventListener('DOMContentLoaded', initLanding);
}
