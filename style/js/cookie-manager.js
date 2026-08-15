/** Cookie usage notice. Analytics is initialized separately in page HTML. */
(function () {
    'use strict';
    const COOKIE_NAME = 'cookie_notice_accepted_v1';
    const COOKIE_EXPIRE_DAYS = 365;

    function setCookie(name, value, days) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax' + secure;
    }

    function getCookie(name) {
        const prefix = name + '=';
        const item = document.cookie.split(';').map(function (value) { return value.trim(); })
            .find(function (value) { return value.indexOf(prefix) === 0; });
        return item ? decodeURIComponent(item.substring(prefix.length)) : null;
    }

    function renderBanner(banner) {
        banner.id = 'cookie-consent';
        banner.className = 'cookie-consent';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Уведомление об использовании cookie');
        banner.innerHTML = '<div class="cookie-content">' +
            '<div class="cookie-text"><strong>Мы используем cookie</strong>' +
            '<p>Оставаясь на сайте, вы соглашаетесь на использование файлов cookie и обработку данных сервисами аналитики.</p></div>' +
            '<div class="cookie-buttons"><button id="cookie-accept" class="cookie-btn accept" type="button">Принять</button>' +
            '<a class="cookie-btn details" href="/privacy-policy.html">Подробнее</a></div></div>';
        return banner;
    }

    function createBanner() {
        const banner = renderBanner(document.createElement('div'));
        document.body.appendChild(banner);
        return banner;
    }

    function initCookieNotice() {
        let banner = document.getElementById('cookie-consent');
        if (banner) renderBanner(banner);
        else banner = createBanner();
        if (getCookie(COOKIE_NAME) === 'yes') {
            banner.style.display = 'none';
            return;
        }
        banner.style.display = 'block';
        setTimeout(function () { banner.classList.add('show'); }, 10);
        const acceptButton = document.getElementById('cookie-accept');
        if (acceptButton) acceptButton.addEventListener('click', function () {
            setCookie(COOKIE_NAME, 'yes', COOKIE_EXPIRE_DAYS);
            banner.classList.remove('show');
            setTimeout(function () { banner.style.display = 'none'; }, 400);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCookieNotice);
    else initCookieNotice();
})();
