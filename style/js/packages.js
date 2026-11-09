(() => {
    const root = document.querySelector('.home-package--carousel');
    if (!root) return;
    const track = root.querySelector('.home-package__track');
    const slides = [...track.children];
    const choices = [...root.querySelectorAll('[data-package]')];
    const prev = root.querySelector('[data-package-prev]');
    const next = root.querySelector('[data-package-next]');
    let current = 0;
    function update() {
        const left = track.getBoundingClientRect().left;
        current = slides.reduce((best, slide, i) =>
            Math.abs(slide.getBoundingClientRect().left - left) <
            Math.abs(slides[best].getBoundingClientRect().left - left) ? i : best, 0);
        slides.forEach((slide, i) => { slide.inert = i !== current; });
        choices.forEach((button, i) => button.setAttribute('aria-pressed', String(i === current)));
        track.style.height = `${Math.ceil(slides[current].getBoundingClientRect().height) + 2}px`;
        root.style.setProperty('--package-arrow-top', `${track.offsetTop + Math.min(220, slides[current].querySelector('.home-package__summary').offsetHeight / 2)}px`);
        prev.disabled = current === 0;
        next.disabled = current === slides.length - 1;
    }
    function go(index) {
        const target = Math.max(0, Math.min(slides.length - 1, index));
        track.scrollTo({
            left: track.scrollLeft + slides[target].getBoundingClientRect().left - track.getBoundingClientRect().left,
            behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'
        });
    }
    prev.addEventListener('click', () => go(current - 1));
    choices.forEach((button, index) => button.addEventListener('click', () => go(index)));
    next.addEventListener('click', () => go(current + 1));
    track.addEventListener('keydown', event => {
        if (event.target !== track) return;
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            go(current + (event.key === 'ArrowRight' ? 1 : -1));
        }
    });
    track.addEventListener('scroll', update, { passive: true });
    const observer = new ResizeObserver(update);
    slides.forEach(slide => observer.observe(slide));
    root.querySelector('.home-package__navigation').hidden = false;
    update();
})();
