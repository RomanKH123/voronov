/**
 * cookie-manager.js
 * Управление согласием на cookie с плавной анимацией
 */

(function() {
    'use strict';
    
    // Версия согласия. При существенном обновлении политики ПДн
    // увеличиваем число — все посетители увидят баннер повторно.
    const COOKIE_NAME = 'cookie_consent_v2';
    const COOKIE_EXPIRE_DAYS = 365;

    function loadAnalytics() {
        if (window.__analyticsLoaded) return;
        window.__analyticsLoaded = true;

        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function() { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', 'G-JFKDPNHH6E', { anonymize_ip: true });
        const googleScript = document.createElement('script');
        googleScript.async = true;
        googleScript.src = 'https://www.googletagmanager.com/gtag/js?id=G-JFKDPNHH6E';
        document.head.appendChild(googleScript);

        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            k=e.createElement(t); a=e.getElementsByTagName(t)[0];
            k.async=1; k.src=r; a.parentNode.insertBefore(k,a);
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=106882746', 'ym');
        window.ym(106882746, 'init', {
            ssr: true,
            webvisor: true,
            clickmap: true,
            accurateTrackBounce: true,
            trackLinks: true
        });
    }
    
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value || '') + expires + '; path=/; SameSite=Lax' + secure;
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
        return null;
    }
    
    function initCookieConsent() {
        const consent = getCookie(COOKIE_NAME);
        const banner = document.getElementById('cookie-consent');
        
        if (!banner) return;
        
        // Если уже есть согласие или отказ — скрываем баннер
        if (consent === 'accepted') {
            loadAnalytics();
            banner.style.display = 'none';
            return;
        }
        if (consent === 'declined') {
            banner.style.display = 'none';
            return;
        }
        
        // Показываем баннер (сначала делаем видимым, потом добавляем класс для анимации)
        banner.style.display = 'block';
        
        // Небольшая задержка для корректной анимации
        setTimeout(() => {
            banner.classList.add('show');
        }, 10);
        
        // Кнопка "Принять"
        const acceptBtn = document.getElementById('cookie-accept');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', function() {
                setCookie(COOKIE_NAME, 'accepted', COOKIE_EXPIRE_DAYS);
                loadAnalytics();
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 400);
                
            });
        }
        
        // Кнопка "Отклонить"
        const declineBtn = document.getElementById('cookie-decline');
        if (declineBtn) {
            declineBtn.addEventListener('click', function() {
                setCookie(COOKIE_NAME, 'declined', COOKIE_EXPIRE_DAYS);
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 400);
            });
        }
    }
    
    // Запускаем при загрузке страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieConsent);
    } else {
        initCookieConsent();
    }
    
})();
