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
    
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Lax';
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    function initCookieConsent() {
        const consent = getCookie(COOKIE_NAME);
        const banner = document.getElementById('cookie-consent');
        
        if (!banner) return;
        
        // Если уже есть согласие или отказ — скрываем баннер
        if (consent === 'accepted' || consent === 'declined') {
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
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 400);
                
                // Активируем Яндекс.Метрику (если была отключена)
                if (typeof ym !== 'undefined') {
                    ym(106882746, 'hit', window.location.href);
                }
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