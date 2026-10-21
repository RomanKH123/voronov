(() => {
    'use strict';

    const cards = document.querySelectorAll('.creation-page .service-card, .creation-page .service-step, .home-details :is(.ln_1, .ln_2, .ln_3, .blok_5_1, .blok_5_2, .blok_4_1, .blok_4_2, .blok_4_3, .why-card)');
    const pointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    cards.forEach((card) => {
        let frame = 0;
        card.addEventListener('pointermove', (event) => {
            if (!pointer.matches || event.pointerType === 'touch') return;
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--light-x', `${event.clientX - rect.left}px`);
                card.style.setProperty('--light-y', `${event.clientY - rect.top}px`);
                card.classList.add('is-lit');
            });
        });
        const clearLight = () => {
            cancelAnimationFrame(frame);
            card.classList.remove('is-lit');
        };
        card.addEventListener('pointerleave', clearLight);
        card.addEventListener('pointercancel', clearLight);
    });

    // Content stays visible if scripts or IntersectionObserver are unavailable.
    if (!('IntersectionObserver' in window) || reducedMotion.matches) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const siblings = Array.from(entry.target.parentElement.children);
            entry.target.style.setProperty('--reveal-delay', `${siblings.indexOf(entry.target) % 3 * 70}ms`);
            entry.target.classList.add('detail-reveal');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    cards.forEach((card) => {
        if (card.closest('.creation-page')) observer.observe(card);
    });
})();
