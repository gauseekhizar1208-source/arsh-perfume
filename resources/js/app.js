import Alpine from 'alpinejs';
import './storefront-motion';
window.Alpine = Alpine;
Alpine.start();

// Arsh Perfume - small theme helpers.
//  - back-to-top button (peach square, bottom right)
//  - soft shadow under the sticky header once the page is scrolled
//  - OPTIONAL scrolling announcement strip above the header (off by default)
//
// Save as resources/js/arsh-theme.js and add ONE line at the end of resources/js/app.js:
//     import './arsh-theme';

const SHOW_TICKER = false; // set to true to show the scrolling strip above the header
const TICKER_TEXT = [
    'Elevate your presence',
    'Premium attars & perfumes',
    'Handcrafted with care',
    'Delivered across India',
    'Feel the best quality',
];

function init() {
    if (document.querySelector('[data-upi-qr]')) {
        import('./upi-qr').catch(() => {
            const status = document.querySelector('[data-qr-status]');
            if (status) status.textContent = 'The QR could not load. Use the UPI ID shown below to pay.';
        });
    }
    // ---- back to top ----
    const btn = document.createElement('a');
    btn.href = '#top';
    btn.className = 'to-top';
    btn.hidden = true;
    btn.setAttribute('aria-label', 'Back to top');
    btn.innerHTML =
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
    document.body.appendChild(btn);

    // ---- header shadow + button visibility on scroll ----
    const header = document.querySelector('body > header');
    let ticking = false;
    const update = () => {
        const y = window.scrollY;
        btn.hidden = y < 400;
        if (header) header.classList.toggle('is-scrolled', y > 8);
        ticking = false;
    };
    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(update);
        }
    }, { passive: true });
    update();

    // ---- optional announcement strip ----
    if (SHOW_TICKER) {
        const bar = document.createElement('div');
        bar.setAttribute('aria-label', 'Announcements');
        bar.style.cssText = 'overflow:hidden;background:#F4F1EC;border-bottom:1px solid #E6E1DA;';
        const row = TICKER_TEXT.map((t) => `<span>${t.replace(/&/g, '&amp;')} &bull;</span>`).join('');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        bar.innerHTML =
            '<div style="display:flex;width:max-content;gap:3rem;padding:.55rem 0;font-size:.8rem;font-weight:700;' +
            'text-transform:uppercase;white-space:nowrap;color:#1F2430;' +
            (reduce ? '' : 'animation:arsh-marquee 38s linear infinite;') + '">' +
            `<div style="display:flex;gap:3rem">${row}${row}</div><div style="display:flex;gap:3rem" aria-hidden="true">${row}${row}</div></div>`;
        const style = document.createElement('style');
        style.textContent = '@keyframes arsh-marquee{to{transform:translateX(-50%)}}';
        document.head.appendChild(style);
        document.body.prepend(bar);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
