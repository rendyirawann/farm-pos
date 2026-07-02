import Swiper from 'swiper';
import { Mousewheel, Keyboard, Pagination, Navigation } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

// Perangkat sentuh / layar <= 1024px (HP, tablet, iPad) -> BUKAN PC/laptop
function isMobileLayout() {
    try {
        return window.matchMedia('(max-width: 1024px), (hover: none) and (pointer: coarse)').matches;
    } catch (e) {
        return window.innerWidth <= 1024;
    }
}

function initLanding() {
    const el = document.querySelector('#landing-swiper');
    if (!el) return;

    // ===== BURGER MENU (mobile) =====
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

    // ===== TOMBOL KEMBALI KE ATAS (mobile) =====
    const toTop = document.getElementById('lp-totop');
    if (toTop) {
        toTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== MOBILE / TABLET: scroll VERTIKAL biasa (tanpa Swiper) + tombol floating =====
    if (isMobileLayout()) {
        document.documentElement.classList.add('lp-mobile');
        // Anchor (#harga dari tombol "Pesan Sekarang") pakai smooth-scroll native.
        return;
    }

    // ===== DESKTOP (PC/laptop): Swiper horizontal =====
    const swiper = new Swiper(el, {
        modules: [Mousewheel, Keyboard, Pagination, Navigation],
        speed: 650,
        slidesPerView: 1,
        simulateTouch: false,          // matikan drag pakai mouse
        allowTouchMove: true,
        mousewheel: {
            forceToAxis: false,   // roda mouse vertikal -> tetap pindah slide horizontal
            sensitivity: 1,
            releaseOnEdges: true, // saat konten slide lebih tinggi dari layar, scroll konten dulu baru pindah slide
            thresholdDelta: 40,   // abaikan wheel kecil (noise trackpad) biar tidak sensitif
            thresholdTime: 700,   // min 700ms antar perpindahan -> 1x scroll = 1 slide (bukan lompat 2)
        },
        keyboard: { enabled: true },
        pagination: { el: '.landing-pagination', clickable: true },
        navigation: { nextEl: '.landing-next', prevEl: '.landing-prev' },
        on: {
            progress(sw) {
                const bar = document.getElementById('prog');
                if (bar) bar.style.width = (sw.progress * 100) + '%';
            },
        },
    });

    // Link menu (#fitur, #galeri, #harga) -> pindah ke slide terkait
    document.querySelectorAll('a[href^="#"]').forEach((a) => {
        a.addEventListener('click', (e) => {
            const id = a.getAttribute('href').slice(1);
            const slide = id && document.getElementById(id);
            if (slide && slide.classList.contains('swiper-slide')) {
                e.preventDefault();
                const idx = Array.prototype.indexOf.call(slide.parentElement.children, slide);
                swiper.slideTo(idx);
            }
        });
    });
}

if (document.readyState !== 'loading') {
    initLanding();
} else {
    document.addEventListener('DOMContentLoaded', initLanding);
}
