import { animate as motionAnimate } from 'framer-motion/dom/mini';
import { inView, stagger } from 'framer-motion/dom';

// Progressive enhancement: content remains visible if motion or JavaScript is unavailable.
function initStorefrontMotion() {
    const root = document.querySelector('.storefront');
    if (!root || root.classList.contains('admin-app')) return;

    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = matchMedia('(hover: hover) and (pointer: fine)');
    const animations = new Map();
    const tiltResets = [];
    const animate = (target, frames, options = {}) => {
        const elements = target instanceof Element ? [target] : Array.from(target);
        if (reduced.matches || !elements.length || elements.some(element => !element.animate)) return;
        // Restore CSS ownership after each entrance so pointer tilt and 3D transforms stay intact.
        const previous = elements.map(element => ({
            element, opacity: element.style.opacity, transform: element.style.transform,
        }));
        const animation = motionAnimate(elements, frames, {
            duration: 0.65, ease: [0.2, 0.7, 0.2, 1], ...options,
        });
        const cleanup = () => {
            if (!animations.has(animation)) return;
            animation.stop();
            animations.delete(animation);
            previous.forEach(({ element, opacity, transform }) => {
                if (opacity) element.style.opacity = opacity;
                else element.style.removeProperty('opacity');
                if (transform) element.style.transform = transform;
                else element.style.removeProperty('transform');
            });
        };
        animations.set(animation, cleanup);
        animation.finished.then(cleanup, cleanup);
    };

    const rise = { opacity: [0, 1], transform: ['translate3d(0, 24px, 0)', 'none'] };
    const heroCopy = root.querySelectorAll('.hero-copy > *');
    animate(heroCopy, rise, { duration: 0.8, delay: stagger(0.09), ease: [0.16, 1, 0.3, 1] });

    // Motion's viewport observers play each entrance once, without hiding unloaded content.
    if ('IntersectionObserver' in window) {
        const sections = root.querySelectorAll('.section-heading, .story-grid, .newsletter-grid');
        inView(sections, element => { animate(element, rise); }, { amount: 0.08 });
        inView(root.querySelectorAll('.collection-grid'), grid => {
            // Opacity alone leaves the cards' CSS perspective transforms available during entry.
            animate(grid.querySelectorAll('.collection-tile'), { opacity: [0, 1] }, { duration: 0.75, delay: stagger(0.1) });
        }, { amount: 0.08 });
        inView(root.querySelectorAll('.product-card'), card => { animate(card, rise, { duration: 0.75 }); }, { amount: 0.12 });
        inView(root.querySelectorAll('.service-grid'), grid => {
            animate(grid.children, rise, { delay: stagger(0.1) });
        }, { amount: 0.08 });
    }

    const hero = root.querySelector('.hero-art');
    if (hero) animate(hero, {
        opacity: [0, 1],
        transform: ['perspective(1100px) rotateY(-12deg) translate3d(0, 22px, 0)', 'none'],
    }, { duration: 1.1, delay: 0.12, ease: [0.16, 1, 0.3, 1] });

    // The sculpture has real CSS 3D faces; rotation works with a mouse or the keyboard slider.
    const scene = root.querySelector('[data-fragrance-scene]');
    if (scene?.querySelector('.bottle-stage') && scene.querySelector('[data-bottle-rotation]')) {
        const stage = scene.querySelector('.bottle-stage');
        const slider = scene.querySelector('[data-bottle-rotation]');
        let drag = null;
        const rotate = value => {
            const angle = Math.max(-55, Math.min(55, Math.round(value)));
            scene.style.setProperty('--bottle-y', `${angle}deg`);
            slider.value = angle;
            slider.setAttribute('aria-valuetext', angle === 0 ? 'Facing forward' : `${Math.abs(angle)} degrees ${angle < 0 ? 'left' : 'right'}`);
        };
        slider.addEventListener('input', () => rotate(Number(slider.value)));
        stage.addEventListener('pointerdown', event => {
            // Touch visitors use the native slider so vertical page scrolling stays natural.
            if (event.pointerType !== 'mouse' || event.button !== 0) return;
            drag = { x: event.clientX, angle: Number(slider.value), id: event.pointerId };
            stage.setPointerCapture(event.pointerId);
            stage.classList.add('is-dragging');
        });
        stage.addEventListener('pointermove', event => {
            if (!drag) return;
            rotate(drag.angle + (event.clientX - drag.x) * 0.5);
        });
        const stopDrag = () => {
            drag = null;
            stage.classList.remove('is-dragging');
        };
        stage.addEventListener('pointerup', stopDrag);
        stage.addEventListener('pointercancel', stopDrag);
        stage.addEventListener('lostpointercapture', stopDrag);
        window.addEventListener('blur', stopDrag);
    }

    // Pointer tracking runs only while a mouse is over a surface; no idle render loop.
    root.querySelectorAll('.product-image, .collection-tile').forEach(element => {
        element.classList.add('motion-surface');
        let bounds;
        let frame = 0;
        let point;
        const reset = () => {
            cancelAnimationFrame(frame);
            frame = 0;
            bounds = null;
            element.classList.remove('is-tilting');
            element.style.removeProperty('--tilt-x');
            element.style.removeProperty('--tilt-y');
            element.style.removeProperty('--light-x');
            element.style.removeProperty('--light-y');
        };
        tiltResets.push(reset);
        element.addEventListener('pointerenter', event => {
            if (reduced.matches || !finePointer.matches || event.pointerType !== 'mouse') return;
            bounds = element.getBoundingClientRect();
        });
        element.addEventListener('pointermove', event => {
            if (reduced.matches || !finePointer.matches || event.pointerType !== 'mouse') return;
            // Scrolling/resizing invalidates the old geometry, but a pointer already
            // over the card should resume tracking without having to leave it first.
            bounds ??= element.getBoundingClientRect();
            point = { x: event.clientX, y: event.clientY };
            if (frame) return;
            frame = requestAnimationFrame(() => {
                frame = 0;
                const x = Math.max(0, Math.min(1, (point.x - bounds.left) / bounds.width));
                const y = Math.max(0, Math.min(1, (point.y - bounds.top) / bounds.height));
                const strength = 6;
                element.style.setProperty('--tilt-x', `${(0.5 - y) * strength}deg`);
                element.style.setProperty('--tilt-y', `${(x - 0.5) * strength}deg`);
                element.style.setProperty('--light-x', `${x * 100}%`);
                element.style.setProperty('--light-y', `${y * 100}%`);
                element.classList.add('is-tilting');
            });
        }, { passive: true });
        element.addEventListener('pointerleave', reset);
        element.addEventListener('pointercancel', reset);
    });

    const resetMotion = () => {
        tiltResets.forEach(reset => reset());
        if (reduced.matches) animations.forEach(cleanup => cleanup());
    };
    reduced.addEventListener('change', resetMotion);
    finePointer.addEventListener('change', resetMotion);
    window.addEventListener('resize', resetMotion, { passive: true });
    window.addEventListener('scroll', () => tiltResets.forEach(reset => reset()), { passive: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStorefrontMotion, { once: true });
} else {
    initStorefrontMotion();
}
