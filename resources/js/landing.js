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

    initPriceCarousel();
}

// ===== Carousel kartu harga (bersarang): 2 kartu/tampilan, tombol, drag mouse, wheel -> geser kartu.
// loop:false + wrap manual (mentok kanan balik ke awal) supaya tombol durasi/harga tetap berfungsi
// (loop bawaan Swiper mengkloning slide -> handler klik pill tidak ikut terkloning). =====
function initPriceCarousel() {
    const el = document.querySelector('.mooda-price-carousel');
    if (!el) return;

    const priceSwiper = new Swiper(el, {
        speed: 450,
        slidesPerView: 2,
        slidesPerGroup: 2,        // geser dua-dua (1-2 lalu 3-4)
        spaceBetween: 18,
        grabCursor: true,         // kursor "grab"
        simulateTouch: true,      // klik-tahan lalu geser kiri/kanan pakai mouse
        allowTouchMove: true,
        nested: true,             // jangan ganggu swiper section (luar)
        watchOverflow: true,
        breakpoints: {
            0:   { slidesPerView: 1, slidesPerGroup: 1, spaceBetween: 14 },
            900: { slidesPerView: 2, slidesPerGroup: 2, spaceBetween: 18 },
        },
    });

    // Maju/mundur dengan wrap: mentok kanan -> reset ke slide awal; mentok kiri -> ke akhir.
    const go = (fwd) => {
        if (fwd) { priceSwiper.isEnd ? priceSwiper.slideTo(0) : priceSwiper.slideNext(); }
        else { priceSwiper.isBeginning ? priceSwiper.slideTo(priceSwiper.slides.length - 1) : priceSwiper.slidePrev(); }
    };
    const prevBtn = document.querySelector('.price-prev');
    const nextBtn = document.querySelector('.price-next');
    if (prevBtn) prevBtn.addEventListener('click', () => go(false));
    if (nextBtn) nextBtn.addEventListener('click', () => go(true));

    // Scroll roda mouse di ATAS area kartu -> geser kartu (bukan pindah section), dan tak berujung.
    // Capture-phase + stopPropagation mencegah mousewheel swiper section (luar) ikut bereaksi.
    let wheelLock = false;
    el.addEventListener('wheel', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (wheelLock) return;
        wheelLock = true;
        const delta = Math.abs(e.deltaY) >= Math.abs(e.deltaX) ? e.deltaY : e.deltaX;
        go(delta > 0);
        setTimeout(() => { wheelLock = false; }, 260);
    }, { capture: true, passive: false });
}

if (document.readyState !== 'loading') {
    initLanding();
} else {
    document.addEventListener('DOMContentLoaded', initLanding);
}
