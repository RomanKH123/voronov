(() => {
    const storageKey = "ezhevika_cookie_consent";
    const consentVersion = 2;
    const banner = document.querySelector(".cookie-consent");
    const settingsButtons = document.querySelectorAll("[data-cookie-settings]");
    const status = document.querySelector("[data-cookie-status]");
    let memoryConsent = null;
    let analyticsActivated = false;

    const readConsent = () => {
        try {
            const consent = JSON.parse(localStorage.getItem(storageKey));
            return consent && consent.version === consentVersion ? consent : memoryConsent;
        } catch {
            return memoryConsent;
        }
    };

    const activateAnalytics = () => {
        analyticsActivated = true;
        document.querySelectorAll('script[type="text/plain"][data-consent="analytics"]').forEach((source) => {
            if (source.dataset.activated === "true") return;

            const script = document.createElement("script");
            if (source.dataset.src) script.src = source.dataset.src;
            if (!source.dataset.src) script.textContent = source.textContent;
            script.async = source.hasAttribute("data-async");
            script.defer = source.hasAttribute("data-defer");
            source.dataset.activated = "true";
            source.after(script);
        });
        document.querySelectorAll('[data-consent="analytics"][data-src]:not(script)').forEach((element) => {
            if (element.dataset.activated === "true") return;
            element.src = element.dataset.src;
            element.dataset.activated = "true";
        });
        window.dispatchEvent(new CustomEvent("analytics-consent-granted"));
    };

    const saveConsent = (level) => {
        const consent = { level, version: consentVersion, updatedAt: new Date().toISOString() };
        memoryConsent = consent;
        try {
            localStorage.setItem(storageKey, JSON.stringify(consent));
        } catch {
            // Выбор действует до закрытия вкладки, даже если localStorage недоступен.
        }
        if (banner) banner.hidden = true;
        if (status) status.textContent = level === "analytics"
            ? "Аналитика разрешена."
            : "Разрешены только необходимые технологии.";
        if (level === "analytics") activateAnalytics();
    };

    document.querySelectorAll("[data-cookie-choice]").forEach((button) => {
        button.addEventListener("click", () => saveConsent(button.dataset.cookieChoice));
    });

    settingsButtons.forEach((button) => {
        button.addEventListener("click", () => {
            memoryConsent = null;
            try {
                localStorage.removeItem(storageKey);
            } catch {
                // Настройки браузера могут запрещать доступ к localStorage.
            }
            if (banner) {
                banner.hidden = false;
                banner.querySelector("button").focus();
            } else if (status) {
                status.textContent = "Выбор сброшен. На главной странице можно выбрать настройки заново.";
            }
        });
    });

    const consent = readConsent();
    if (!consent) {
        if (banner) banner.hidden = false;
    } else {
        if (banner) banner.hidden = true;
        if (consent.level === "analytics") activateAnalytics();
        if (status) status.textContent = consent.level === "analytics"
            ? "Аналитика разрешена."
            : "Разрешены только необходимые технологии.";
    }
})();
