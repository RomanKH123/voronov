const navToggle = document.querySelector(".nav-toggle");
const siteNav = document.querySelector(".site-nav");
const navLinks = document.querySelectorAll(".site-nav a");
const siteHeader = document.querySelector(".site-header");
const scrollTopButton = document.querySelector(".scroll-top");

if (navToggle && siteNav) {
    navToggle.addEventListener("click", () => {
        const isOpen = navToggle.getAttribute("aria-expanded") === "true";

        navToggle.setAttribute("aria-expanded", String(!isOpen));
        siteNav.classList.toggle("is-open", !isOpen);
    });

    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navToggle.setAttribute("aria-expanded", "false");
            siteNav.classList.remove("is-open");
        });
    });
}

const updateScrollUI = () => {
    const isScrolled = window.scrollY > 16;
    const showScrollTop = window.scrollY > 420;

    if (siteHeader) {
        siteHeader.classList.toggle("site-header--stuck", isScrolled);
    }

    if (scrollTopButton) {
        scrollTopButton.classList.toggle("is-visible", showScrollTop);
    }
};

if (scrollTopButton) {
    scrollTopButton.addEventListener("click", () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    });
}

window.addEventListener("scroll", updateScrollUI, { passive: true });
window.addEventListener("resize", updateScrollUI);
updateScrollUI();

const heroSlider = document.querySelector(".hero-slider");

if (heroSlider) {
    const heroImages = Array.from(heroSlider.querySelectorAll(".hero-slider__image"));
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let activeImage = Math.max(heroImages.findIndex((image) => image.classList.contains("is-active")), 0);
    let sliderTimer;

    const showNextHeroImage = () => {
        heroImages[activeImage].classList.remove("is-active");
        activeImage = (activeImage + 1) % heroImages.length;
        heroImages[activeImage].classList.add("is-active");
    };

    const startHeroSlider = () => {
        if (heroImages.length > 1 && !reduceMotion && !sliderTimer) {
            sliderTimer = window.setInterval(showNextHeroImage, 4000);
        }
    };

    const stopHeroSlider = () => {
        window.clearInterval(sliderTimer);
        sliderTimer = undefined;
    };

    const restartHeroSlider = () => {
        stopHeroSlider();

        if (!document.hidden) {
            startHeroSlider();
        }
    };

    document.addEventListener("visibilitychange", () => {
        document.hidden ? stopHeroSlider() : restartHeroSlider();
    });
    window.addEventListener("pageshow", restartHeroSlider);
    window.addEventListener("focus", restartHeroSlider);

    startHeroSlider();
}

const aboutPoints = Array.from(document.querySelectorAll("#why-us .about-point"));

if (aboutPoints.length) {
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (reduceMotion) {
        aboutPoints.forEach((point) => point.classList.add("is-visible"));
    } else {
        const aboutObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.add("is-visible");
                entry.target.addEventListener("transitionend", function handleReveal(event) {
                    if (event.propertyName !== "transform") return;

                    entry.target.classList.remove("about-point--ready");
                    entry.target.removeEventListener("transitionend", handleReveal);
                });
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.14,
            rootMargin: "0px 0px 8% 0px",
        });

        aboutPoints.forEach((point) => {
            point.classList.add("about-point--ready");
            aboutObserver.observe(point);
        });
    }
}

const zoneCards = document.querySelectorAll(".zone-card");
const zoneModal = document.querySelector("#zone-modal");

if (zoneCards.length && zoneModal) {
    const modalTitle = zoneModal.querySelector("#zone-modal-title");
    const modalDescription = zoneModal.querySelector("#zone-modal-description");
    const modalImage = zoneModal.querySelector("#zone-modal-image");
    const modalCounter = zoneModal.querySelector(".zone-modal__counter");
    const modalThumbs = zoneModal.querySelector(".zone-modal__thumbs");
    const previousButton = zoneModal.querySelector("[data-gallery-prev]");
    const nextButton = zoneModal.querySelector("[data-gallery-next]");
    const closeButton = zoneModal.querySelector(".zone-modal__close");
    let galleryImages = [];
    let currentImage = 0;
    let openedFrom;

    const showGalleryImage = (index) => {
        if (!galleryImages.length) return;

        currentImage = (index + galleryImages.length) % galleryImages.length;
        const image = galleryImages[currentImage];
        modalImage.src = image.src;
        modalImage.alt = image.alt;
        modalCounter.textContent = `${currentImage + 1} / ${galleryImages.length}`;

        modalThumbs.querySelectorAll(".zone-modal__thumb").forEach((thumb, thumbIndex) => {
            const isActive = thumbIndex === currentImage;
            thumb.classList.toggle("is-active", isActive);
            thumb.setAttribute("aria-current", String(isActive));

            if (isActive) thumb.scrollIntoView({ block: "nearest", inline: "nearest" });
        });
    };

    const openZoneModal = (card) => {
        const galleryTemplate = card.querySelector(".zone-gallery");
        galleryImages = galleryTemplate
            ? Array.from(galleryTemplate.content.querySelectorAll("img"), (image) => ({
                src: image.getAttribute("src"),
                alt: image.getAttribute("alt") || card.querySelector("h3").textContent,
            }))
            : [];

        if (!galleryImages.length) return;

        openedFrom = card;
        modalTitle.textContent = card.querySelector("h3").textContent;
        modalDescription.textContent = card.querySelector(".info-card__content p").textContent;
        modalThumbs.replaceChildren();

        galleryImages.forEach((image, index) => {
            const thumb = document.createElement("button");
            const thumbImage = document.createElement("img");
            thumb.type = "button";
            thumb.className = "zone-modal__thumb";
            thumb.setAttribute("aria-label", `Показать фото ${index + 1}`);
            thumbImage.src = image.src;
            thumbImage.alt = "";
            thumbImage.loading = "lazy";
            thumbImage.decoding = "async";
            thumb.append(thumbImage);
            thumb.addEventListener("click", () => showGalleryImage(index));
            modalThumbs.append(thumb);
        });

        const hasSeveralImages = galleryImages.length > 1;
        previousButton.hidden = !hasSeveralImages;
        nextButton.hidden = !hasSeveralImages;
        modalThumbs.hidden = !hasSeveralImages;
        zoneModal.hidden = false;
        document.body.classList.add("has-open-modal");
        showGalleryImage(0);
        closeButton.focus();
    };

    const eventCards = Array.from(document.querySelectorAll(".event-card"));
    const eventImages = eventCards.map((card) => {
        const image = card.querySelector("img");

        return {
            src: image.getAttribute("src"),
            alt: image.getAttribute("alt") || "Мероприятие в лофте",
        };
    });

    const openEventModal = (card, startIndex) => {
        galleryImages = eventImages;
        openedFrom = card;
        modalTitle.textContent = "Наши мероприятия";
        modalDescription.textContent = "Фотографии праздников и мероприятий в лофте «Ежевика».";
        modalThumbs.replaceChildren();

        galleryImages.forEach((image, index) => {
            const thumb = document.createElement("button");
            const thumbImage = document.createElement("img");
            thumb.type = "button";
            thumb.className = "zone-modal__thumb";
            thumb.setAttribute("aria-label", `Показать фото ${index + 1}`);
            thumbImage.src = image.src;
            thumbImage.alt = "";
            thumbImage.loading = "lazy";
            thumbImage.decoding = "async";
            thumb.append(thumbImage);
            thumb.addEventListener("click", () => showGalleryImage(index));
            modalThumbs.append(thumb);
        });

        previousButton.hidden = false;
        nextButton.hidden = false;
        modalThumbs.hidden = false;
        zoneModal.hidden = false;
        document.body.classList.add("has-open-modal");
        showGalleryImage(startIndex);
        closeButton.focus();
    };

    const closeZoneModal = () => {
        zoneModal.hidden = true;
        document.body.classList.remove("has-open-modal");
        modalImage.removeAttribute("src");

        if (openedFrom) openedFrom.focus();
    };

    zoneCards.forEach((card) => {
        card.addEventListener("click", () => openZoneModal(card));
        card.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                openZoneModal(card);
            }
        });
    });

    eventCards.forEach((card, index) => {
        card.tabIndex = 0;
        card.setAttribute("role", "button");
        card.setAttribute("aria-haspopup", "dialog");
        card.addEventListener("click", () => openEventModal(card, index));
        card.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                openEventModal(card, index);
            }
        });
    });

    zoneModal.querySelectorAll("[data-modal-close]").forEach((element) => {
        element.addEventListener("click", closeZoneModal);
    });
    previousButton.addEventListener("click", () => showGalleryImage(currentImage - 1));
    nextButton.addEventListener("click", () => showGalleryImage(currentImage + 1));

    document.addEventListener("keydown", (event) => {
        if (zoneModal.hidden) return;

        if (event.key === "Escape") closeZoneModal();
        if (event.key === "ArrowLeft") showGalleryImage(currentImage - 1);
        if (event.key === "ArrowRight") showGalleryImage(currentImage + 1);
    });
}

const balloonGalleryItems = document.querySelectorAll(".balloon-gallery figure");
const balloonModal = document.querySelector("#balloon-modal");

if (balloonGalleryItems.length && balloonModal) {
    const modalTitle = balloonModal.querySelector("#balloon-modal-title");
    const modalImage = balloonModal.querySelector("[data-balloon-image]");
    const modalCounter = balloonModal.querySelector(".zone-modal__counter");
    const modalThumbs = balloonModal.querySelector(".zone-modal__thumbs");
    const previousButton = balloonModal.querySelector("[data-balloon-prev]");
    const nextButton = balloonModal.querySelector("[data-balloon-next]");
    const closeButton = balloonModal.querySelector(".zone-modal__close");
    let galleryImages = [];
    let currentImage = 0;
    let openedFrom;

    const showBalloonImage = (index) => {
        currentImage = (index + galleryImages.length) % galleryImages.length;
        modalImage.src = galleryImages[currentImage].src;
        modalImage.alt = galleryImages[currentImage].alt;
        modalCounter.textContent = `${currentImage + 1} / ${galleryImages.length}`;
        modalThumbs.querySelectorAll(".zone-modal__thumb").forEach((thumb, thumbIndex) => {
            const isActive = thumbIndex === currentImage;
            thumb.classList.toggle("is-active", isActive);
            thumb.setAttribute("aria-current", String(isActive));
            if (isActive) thumb.scrollIntoView({ block: "nearest", inline: "nearest" });
        });
    };

    const openBalloonGallery = (item) => {
        const template = item.querySelector(".balloon-gallery__images");
        galleryImages = Array.from(template.content.querySelectorAll("img"), (image) => ({
            src: image.getAttribute("src"),
            alt: image.getAttribute("alt"),
        }));
        openedFrom = item;
        modalTitle.textContent = item.querySelector("figcaption").textContent;
        modalThumbs.replaceChildren();
        galleryImages.forEach((image, index) => {
            const thumb = document.createElement("button");
            const thumbImage = document.createElement("img");
            thumb.type = "button";
            thumb.className = "zone-modal__thumb";
            thumb.setAttribute("aria-label", `Показать фото ${index + 1}`);
            thumbImage.src = image.src;
            thumbImage.alt = "";
            thumbImage.loading = "lazy";
            thumb.append(thumbImage);
            thumb.addEventListener("click", () => showBalloonImage(index));
            modalThumbs.append(thumb);
        });
        balloonModal.hidden = false;
        document.body.classList.add("has-open-modal");
        showBalloonImage(0);
        closeButton.focus();
    };

    const closeBalloonGallery = () => {
        balloonModal.hidden = true;
        document.body.classList.remove("has-open-modal");
        modalImage.removeAttribute("src");
        openedFrom?.focus();
    };

    balloonGalleryItems.forEach((item) => {
        item.addEventListener("click", () => openBalloonGallery(item));
        item.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                openBalloonGallery(item);
            }
        });
    });
    balloonModal.querySelectorAll("[data-balloon-close]").forEach((element) => element.addEventListener("click", closeBalloonGallery));
    previousButton.addEventListener("click", () => showBalloonImage(currentImage - 1));
    nextButton.addEventListener("click", () => showBalloonImage(currentImage + 1));
    document.addEventListener("keydown", (event) => {
        if (balloonModal.hidden) return;
        if (event.key === "Escape") closeBalloonGallery();
        if (event.key === "ArrowLeft") showBalloonImage(currentImage - 1);
        if (event.key === "ArrowRight") showBalloonImage(currentImage + 1);
    });
}

const balloonSlider = document.querySelector("[data-balloon-slider]");

if (balloonSlider) {
    const sliderImage = balloonSlider.querySelector("[data-balloon-slider-image]");
    const sliderCounter = balloonSlider.querySelector("[data-balloon-slider-counter]");
    const sliderPrevious = balloonSlider.querySelector("[data-balloon-slider-prev]");
    const sliderNext = balloonSlider.querySelector("[data-balloon-slider-next]");
    const sliderSourceImages = [
        ...document.querySelectorAll(".balloon-collage img"),
        ...Array.from(document.querySelectorAll(".balloon-gallery__images"), (template) => (
            Array.from(template.content.querySelectorAll("img"))
        )).flat(),
    ];
    const sliderImages = sliderSourceImages.map((image) => ({
        src: image.getAttribute("src"),
        alt: image.getAttribute("alt"),
    }));
    for (let index = sliderImages.length - 1; index > 0; index -= 1) {
        const randomIndex = Math.floor(Math.random() * (index + 1));
        [sliderImages[index], sliderImages[randomIndex]] = [sliderImages[randomIndex], sliderImages[index]];
    }
    let sliderIndex = 0;
    let swipeStartX = 0;

    const showSliderImage = (index) => {
        sliderIndex = (index + sliderImages.length) % sliderImages.length;
        sliderImage.src = sliderImages[sliderIndex].src;
        sliderImage.alt = sliderImages[sliderIndex].alt;
        sliderCounter.textContent = `${sliderIndex + 1} / ${sliderImages.length}`;
    };

    sliderPrevious.addEventListener("click", () => showSliderImage(sliderIndex - 1));
    sliderNext.addEventListener("click", () => showSliderImage(sliderIndex + 1));
    balloonSlider.addEventListener("keydown", (event) => {
        if (event.key === "ArrowLeft") {
            event.preventDefault();
            showSliderImage(sliderIndex - 1);
        }
        if (event.key === "ArrowRight") {
            event.preventDefault();
            showSliderImage(sliderIndex + 1);
        }
    });
    balloonSlider.addEventListener("pointerdown", (event) => {
        swipeStartX = event.clientX;
    });
    balloonSlider.addEventListener("pointerup", (event) => {
        const swipeDistance = event.clientX - swipeStartX;
        if (Math.abs(swipeDistance) < 45) return;
        showSliderImage(sliderIndex + (swipeDistance < 0 ? 1 : -1));
    });

    if (sliderImages.length) showSliderImage(0);
}

const packageModal = document.querySelector("#package-modal");
const packageButtons = document.querySelectorAll("[data-package-open]");
const packageCards = document.querySelectorAll(".package-card");

if (packageModal && packageButtons.length) {
    const modalWindow = packageModal.querySelector(".package-modal__window");
    const modalDescription = packageModal.querySelector(".package-modal__description");
    const closeButton = packageModal.querySelector(".package-modal__close");
    const orderButton = packageModal.querySelector(".package-modal__order-button");
    const orderPanel = packageModal.querySelector(".package-order");
    const orderForm = packageModal.querySelector(".package-order__form");
    const packageSelect = orderForm.querySelector("select[name='package']");
    const formStatus = orderForm.querySelector(".package-order__status");
    let openedFrom;

    const openPackageModal = (card, trigger = card) => {
        const details = card.querySelector(".package-details");
        const gallery = card.querySelector(".package-gallery");
        const packageName = card.querySelector("h3").textContent.trim();

        if (!details) return;

        openedFrom = trigger;
        modalDescription.replaceChildren(details.content.cloneNode(true));
        const title = modalDescription.querySelector("h3");
        title.id = "package-modal-title";

        if (gallery) {
            const images = Array.from(gallery.content.querySelectorAll("img"), (image) => ({
                src: image.getAttribute("src"),
                alt: image.getAttribute("alt") || packageName,
            }));

            if (images.length) {
                const galleryElement = document.createElement("div");
                galleryElement.className = "package-modal__gallery";
                galleryElement.innerHTML = `
                    <div class="package-modal__stage">
                        <button class="package-modal__arrow package-modal__arrow--prev" type="button" aria-label="Предыдущее фото">‹</button>
                        <img src="" alt="">
                        <button class="package-modal__arrow package-modal__arrow--next" type="button" aria-label="Следующее фото">›</button>
                        <span class="package-modal__counter" aria-live="polite"></span>
                    </div>
                    <div class="package-modal__thumbs" aria-label="Фотографии пакета"></div>`;

                const stageImage = galleryElement.querySelector(".package-modal__stage > img");
                const counter = galleryElement.querySelector(".package-modal__counter");
                const thumbs = galleryElement.querySelector(".package-modal__thumbs");
                let imageIndex = 0;

                const showImage = (nextIndex) => {
                    imageIndex = (nextIndex + images.length) % images.length;
                    stageImage.src = images[imageIndex].src;
                    stageImage.alt = images[imageIndex].alt;
                    counter.textContent = `${imageIndex + 1} / ${images.length}`;
                    thumbs.querySelectorAll(".package-modal__thumb").forEach((thumb, index) => {
                        thumb.classList.toggle("is-active", index === imageIndex);
                        thumb.setAttribute("aria-current", index === imageIndex ? "true" : "false");
                    });
                    thumbs.children[imageIndex]?.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "nearest" });
                };

                images.forEach((image, index) => {
                    const thumb = document.createElement("button");
                    thumb.className = "package-modal__thumb";
                    thumb.type = "button";
                    thumb.setAttribute("aria-label", `Показать фото ${index + 1}`);
                    thumb.innerHTML = `<img src="${image.src}" alt="" loading="lazy" decoding="async">`;
                    thumb.addEventListener("click", () => showImage(index));
                    thumbs.append(thumb);
                });

                galleryElement.querySelector(".package-modal__arrow--prev").addEventListener("click", () => showImage(imageIndex - 1));
                galleryElement.querySelector(".package-modal__arrow--next").addEventListener("click", () => showImage(imageIndex + 1));
                modalDescription.insertBefore(galleryElement, title);
                showImage(0);
            }
        }
        packageSelect.value = packageName;
        orderPanel.hidden = true;
        orderButton.hidden = false;
        formStatus.textContent = "";
        orderForm.reset();
        packageSelect.value = packageName;
        packageModal.hidden = false;
        document.body.classList.add("has-open-modal");
        modalWindow.scrollTop = 0;
        closeButton.focus();
    };

    const closePackageModal = () => {
        packageModal.hidden = true;
        document.body.classList.remove("has-open-modal");

        if (openedFrom) openedFrom.focus();
    };

    packageCards.forEach((card) => {
        card.addEventListener("click", (event) => openPackageModal(card, event.target.closest("button") || card));
        card.addEventListener("keydown", (event) => {
            if (event.key !== "Enter" && event.key !== " ") return;
            event.preventDefault();
            openPackageModal(card, card);
        });
    });

    packageModal.querySelectorAll("[data-package-close]").forEach((element) => {
        element.addEventListener("click", closePackageModal);
    });

    orderButton.addEventListener("click", () => {
        orderPanel.hidden = false;
        orderButton.hidden = true;
        orderPanel.scrollIntoView({ behavior: "smooth", block: "start" });
        orderForm.querySelector("input[name='name']").focus({ preventScroll: true });
    });

    orderForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const endpoint = orderForm.dataset.leadEndpoint.trim();
        if (!endpoint) {
            formStatus.textContent = "Онлайн-отправка ещё не подключена. Позвоните нам по номеру выше.";
            return;
        }

        const submitButton = orderForm.querySelector("button[type='submit']");
        const payload = Object.fromEntries(new FormData(orderForm));
        submitButton.disabled = true;
        formStatus.textContent = "Отправляем заявку…";

        try {
            const response = await fetch(endpoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });

            if (!response.ok) throw new Error("Request failed");

            orderForm.reset();
            formStatus.textContent = "Спасибо! Заявка отправлена, мы скоро вам позвоним.";
        } catch {
            formStatus.textContent = "Не удалось отправить заявку. Пожалуйста, позвоните нам по номеру выше.";
        } finally {
            submitButton.disabled = false;
        }
    });

    document.addEventListener("keydown", (event) => {
        if (!packageModal.hidden && event.key === "Escape") closePackageModal();
    });
}

const eventsTrack = document.querySelector("#events-track");
const sliderButtons = document.querySelectorAll(".slider-button");

if (eventsTrack && sliderButtons.length) {
    const updateButtons = () => {
        const maxScroll = eventsTrack.scrollWidth - eventsTrack.clientWidth;
        const startReached = eventsTrack.scrollLeft <= 4;
        const endReached = eventsTrack.scrollLeft >= maxScroll - 4;

        sliderButtons.forEach((button) => {
            const direction = button.dataset.direction;
            button.disabled = direction === "prev" ? startReached : endReached;
        });
    };

    sliderButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const direction = button.dataset.direction === "next" ? 1 : -1;
            const step = Math.max(eventsTrack.clientWidth * 0.72, 280);

            eventsTrack.scrollBy({
                left: direction * step,
                behavior: "smooth",
            });
        });
    });

    eventsTrack.addEventListener("scroll", updateButtons, { passive: true });
    window.addEventListener("resize", updateButtons);
    updateButtons();
}
