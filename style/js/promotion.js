(() => {
    'use strict';
    let leadSubmitted = false;
    const hasSubmitted = () => {
        try { return leadSubmitted || sessionStorage.getItem('lead-submitted') === '1'; }
        catch (_) { return leadSubmitted; }
    };
    if (hasSubmitted()) return;
    window.addEventListener('lead-submitted', () => { leadSubmitted = true; });
    // Show on entry, rather than during internal navigation or history returns.
    const navigation = performance.getEntriesByType('navigation')[0];
    if (navigation?.type === 'back_forward') return;
    if (document.referrer) {
        try {
            if (new URL(document.referrer).origin === window.location.origin) return;
        } catch (_) {}
    }

    const dialog = document.createElement('dialog');
    dialog.className = 'promotion';
    dialog.setAttribute('aria-labelledby', 'promotion-title');
    dialog.innerHTML = `
        <button class="promotion__close" type="button" aria-label="Закрыть акцию">×</button>
        <div class="promotion__layout">
            <img class="promotion__image" src="/img/chatbot-promotion.png" width="1254" height="1254" alt="Акция до конца года: чат-бот для уведомлений о новых заявках при разработке сайта.">
            <div class="promotion__content">
                <span class="promotion__badge">Акция при разработке сайта</span>
                <h2 id="promotion-title">Заявки с сайта —<br>сразу в ваш чат</h2>
                <p>Оставьте имя и телефон, чтобы обсудить разработку сайта и активировать акцию на чат-бота для уведомлений.</p>
                <form class="promotion__form">
                    <label for="promotion-name">Ваше имя</label>
                    <input id="promotion-name" name="name" autocomplete="given-name" minlength="2" maxlength="100" placeholder="Как к вам обращаться?" required>
                    <label for="promotion-phone">Телефон</label>
                    <input id="promotion-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" placeholder="+7 (___) ___-__-__" required>
                    <label class="promotion__consent"><input name="consent" type="checkbox" required><span>Согласен на обработку персональных данных в соответствии с <a href="/privacy-policy.html" target="_blank" rel="noopener">политикой конфиденциальности</a></span></label>
                    <button class="promotion__submit" type="submit">Активировать акцию <span aria-hidden="true">↗</span></button>
                </form>
                <p class="promotion__status" role="status" aria-live="polite"></p>
            </div>
        </div>`;
    document.body.append(dialog);
    const form = dialog.querySelector('form');
    const phone = form.elements.phone;
    const status = dialog.querySelector('.promotion__status');
    const submit = form.querySelector('[type="submit"]');
    let previousFocus;
    let previousOverflow;
    const close = () => dialog.close();
    dialog.querySelector('.promotion__close').addEventListener('click', close);
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    dialog.addEventListener('close', () => {
        document.body.style.overflow = previousOverflow;
        if (previousFocus?.isConnected) previousFocus.focus();
    });
    phone.addEventListener('input', () => phone.setCustomValidity(''));
    form.elements.name.addEventListener('input', () => form.elements.name.setCustomValidity(''));
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submit.disabled) return;
        const name = form.elements.name.value.trim();
        if (name.length < 2) form.elements.name.setCustomValidity('Введите имя: минимум 2 символа.');
        const digits = phone.value.replace(/\D/g, '');
        if (digits.length < 10 || digits.length > 15) phone.setCustomValidity('Введите корректный номер телефона.');
        if (!form.reportValidity()) return;
        submit.disabled = true;
        submit.textContent = 'Отправляем…';
        status.textContent = '';
        try {
            const response = await fetch('/api/main.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, phone: phone.value, email: '',
                    message: 'Акция: чат-бот для уведомлений о новых заявках при разработке сайта. Прошу связаться для активации акции.',
                    form: 'promotion', page: window.location.href, consent: form.elements.consent.checked })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(
                data.errors ? Object.values(data.errors).join(' ') : data.message || 'Не удалось отправить заявку. Попробуйте ещё раз.');
            form.hidden = true;
            status.textContent = 'Заявка на акцию принята! Свяжемся с вами, чтобы обсудить проект и условия подключения чат-бота.';
            leadSubmitted = true;
            try { sessionStorage.setItem('lead-submitted', '1'); } catch (_) {}
            window.dispatchEvent(new Event('lead-submitted'));
            setTimeout(() => { if (dialog.open) dialog.close(); }, 1000);
        } catch (error) {
            status.textContent = error instanceof TypeError ? 'Не удалось связаться с сервером. Проверьте соединение и попробуйте ещё раз.' : error.message;
        } finally {
            submit.disabled = false;
            submit.textContent = 'Активировать акцию ↗';
        }
    });
    let departed = false;
    window.addEventListener('pagehide', () => {
        departed = true;
        if (dialog.open) dialog.close();
    });
    const show = () => {
        if (departed || hasSubmitted()) return;
        // Wait until the visitor finishes interacting with another form or dialog.
        if (document.hidden || document.querySelector('.modal.show, dialog[open]') || document.activeElement?.closest('form')) {
            setTimeout(show, 1000);
            return;
        }
        previousFocus = document.activeElement;
        previousOverflow = document.body.style.overflow;
        dialog.showModal();
        document.body.style.overflow = 'hidden';
    };
    setTimeout(show, 7000);
})();
