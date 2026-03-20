document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы
    const modal = document.getElementById('modal');
    const closeBtn = document.querySelector('.close-modal');
    const contactForm = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');
    const phoneInput = document.getElementById('phone');
    
    // Элементы для формы в контактах
    const contactMainForm = document.getElementById('contactFormMain');
    const successMessageMain = document.getElementById('successMessageMain');
    const phoneMainInput = document.getElementById('phone_main');
    
    // Элементы для липкого меню и стрелки
    const stickyMenu = document.querySelector('.sticky-menu');
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    
    // ===== ФУНКЦИИ ДЛЯ ЛИПКОГО МЕНЮ =====
    
    window.scrollToBlock = function(blockId) {
        const element = document.getElementById(blockId);
        if (element) {
            const menuHeight = stickyMenu ? stickyMenu.offsetHeight : document.querySelector('.menu_st')?.offsetHeight || 0;
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - menuHeight - 20;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
            
            highlightActiveMenuItem(blockId);
        }
    }
    
    function highlightActiveMenuItem(activeId) {
        document.querySelectorAll('.sticky-menu .nav-menu a, .menu_st div').forEach(item => {
            item.classList.remove('active');
        });
        
        const menuMap = {
            'about': 0,
            'services': 1,
            'works': 2,
            'contacts': 3
        };
        
        const menuIndex = menuMap[activeId];
        if (menuIndex !== undefined) {
            const newMenuItems = document.querySelectorAll('.sticky-menu .nav-menu a');
            if (newMenuItems[menuIndex]) {
                newMenuItems[menuIndex].classList.add('active');
            }
            const oldMenuItems = document.querySelectorAll('.menu_st div');
            if (oldMenuItems[menuIndex]) {
                oldMenuItems[menuIndex].classList.add('active');
            }
        }
    }
    
    function updateActiveMenuOnScroll() {
        const sections = [
            { id: 'about', menuIndex: 0 },
            { id: 'services', menuIndex: 1 },
            { id: 'works', menuIndex: 2 },
            { id: 'contacts', menuIndex: 3 }
        ];
        
        const menuHeight = stickyMenu ? stickyMenu.offsetHeight : document.querySelector('.menu_st')?.offsetHeight || 0;
        const scrollPosition = window.pageYOffset + menuHeight + 50;
        
        let currentSection = null;
        
        for (let section of sections) {
            const element = document.getElementById(section.id);
            if (element) {
                const elementTop = element.offsetTop;
                const elementBottom = elementTop + element.offsetHeight;
                
                if (scrollPosition >= elementTop && scrollPosition < elementBottom) {
                    currentSection = section;
                    break;
                }
            }
        }
        
        if (currentSection) {
            document.querySelectorAll('.sticky-menu .nav-menu a, .menu_st div').forEach(item => {
                item.classList.remove('active');
            });
            
            const newMenuItems = document.querySelectorAll('.sticky-menu .nav-menu a');
            if (newMenuItems[currentSection.menuIndex]) {
                newMenuItems[currentSection.menuIndex].classList.add('active');
            }
            
            const oldMenuItems = document.querySelectorAll('.menu_st div');
            if (oldMenuItems[currentSection.menuIndex]) {
                oldMenuItems[currentSection.menuIndex].classList.add('active');
            }
        }
    }
    
    // ===== ФУНКЦИИ ДЛЯ СТРЕЛКИ ПОДЪЁМА =====
    
    window.scrollToTop = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
    
    function handleScroll() {
        if (scrollToTopBtn) {
            if (window.scrollY > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        }
        updateActiveMenuOnScroll();
    }
    
    if (scrollToTopBtn) {
        scrollToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollToTop();
        });
        scrollToTopBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            window.scrollToTop();
        });
    }
    
    // ===== МОДАЛЬНОЕ ОКНО =====
    
    function openModal() {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (contactForm) {
            contactForm.style.display = 'flex';
            contactForm.reset();
        }
        if (successMessage) {
            successMessage.style.display = 'none';
        }
        document.querySelectorAll('.form-group input').forEach(input => {
            input.classList.remove('error');
        });
    }
    
    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    // Кнопки для открытия модального окна
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        const buttonText = button.textContent.trim().toLowerCase();
        if (buttonText === 'подробнее' || buttonText === 'обсудить проект' || buttonText === 'заказать сайт под ключ') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });
        }
    });
    
    // Закрытие модального окна
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closeModal();
        }
    });
    
   // ===== МАСКА ДЛЯ ТЕЛЕФОНА (ИСПРАВЛЕННАЯ ВЕРСИЯ) =====

function phoneMask(input) {
    if (!input) return;
    
    // Функция форматирования номера
    function formatPhoneNumber(digits) {
        if (!digits || digits.length === 0) return '';
        
        // Убираем всё, кроме цифр
        let cleanDigits = digits.replace(/\D/g, '');
        
        // Если нет цифр, возвращаем пустую строку
        if (cleanDigits.length === 0) return '';
        
        // Нормализуем код страны
        if (cleanDigits.startsWith('8')) {
            cleanDigits = '7' + cleanDigits.substring(1);
        } else if (!cleanDigits.startsWith('7') && cleanDigits.length > 0) {
            cleanDigits = '7' + cleanDigits;
        }
        
        // Ограничиваем 11 цифрами
        cleanDigits = cleanDigits.substring(0, 11);
        
        // Форматируем
        let formatted = '';
        
        if (cleanDigits.length === 0) {
            return '';
        }
        
        // Добавляем +7
        formatted = '+7';
        
        // Добавляем код оператора в скобках
        if (cleanDigits.length > 1) {
            const operatorCode = cleanDigits.substring(1, Math.min(4, cleanDigits.length));
            formatted += ' (' + operatorCode;
            
            // Закрываем скобку, если есть хотя бы 3 цифры кода
            if (cleanDigits.length >= 4) {
                formatted += ')';
            }
        }
        
        // Добавляем первую часть номера
        if (cleanDigits.length >= 4) {
            const firstPart = cleanDigits.substring(4, Math.min(7, cleanDigits.length));
            if (firstPart) {
                formatted += ' ' + firstPart;
            }
        }
        
        // Добавляем вторую часть номера
        if (cleanDigits.length >= 7) {
            const secondPart = cleanDigits.substring(7, Math.min(9, cleanDigits.length));
            if (secondPart) {
                formatted += '-' + secondPart;
            }
        }
        
        // Добавляем третью часть номера
        if (cleanDigits.length >= 9) {
            const thirdPart = cleanDigits.substring(9, 11);
            if (thirdPart) {
                formatted += '-' + thirdPart;
            }
        }
        
        return formatted;
    }
    
    // Получаем только цифры из строки
    function getDigits(str) {
        return str.replace(/\D/g, '');
    }
    
    let previousValue = '';
    let previousCursorPos = 0;
    
    input.addEventListener('input', function(e) {
        const oldValue = this.value;
        const cursorPos = this.selectionStart;
        
        // Получаем все цифры из текущего значения
        let digits = getDigits(this.value);
        
        // Если пользователь удалил все цифры
        if (digits.length === 0) {
            this.value = '';
            previousValue = '';
            return;
        }
        
        // Форматируем номер
        const newValue = formatPhoneNumber(digits);
        
        if (newValue !== oldValue) {
            // Сохраняем старую длину для расчета новой позиции курсора
            const oldLength = oldValue.length;
            const newLength = newValue.length;
            
            this.value = newValue;
            
            // Вычисляем новую позицию курсора
            let newCursorPos = cursorPos;
            
            // Определяем, что делал пользователь
            if (e.inputType === 'deleteContentBackward') {
                // Backspace - удаление символа
                newCursorPos = Math.max(0, cursorPos - 1);
            } else if (e.inputType === 'deleteContentForward') {
                // Delete
                newCursorPos = cursorPos;
            } else if (e.inputType === 'insertText') {
                // Вставка текста
                const addedChars = newLength - oldLength;
                newCursorPos = cursorPos + addedChars;
            } else {
                // Другие действия
                newCursorPos = Math.min(newLength, cursorPos);
            }
            
            // Корректируем позицию, чтобы не попадать на служебные символы
            if (newCursorPos > 0 && newCursorPos < newValue.length) {
                // Если курсор стоит на служебном символе, двигаем вперед
                const charAtPos = newValue[newCursorPos];
                if (charAtPos === '+' || charAtPos === '(' || charAtPos === ')' || charAtPos === '-' || charAtPos === ' ') {
                    newCursorPos++;
                }
            }
            
            // Убеждаемся, что курсор в допустимых пределах
            newCursorPos = Math.min(newValue.length, Math.max(0, newCursorPos));
            
            // Устанавливаем курсор
            this.setSelectionRange(newCursorPos, newCursorPos);
        }
        
        previousValue = this.value;
    });
    
    // Обработка нажатия клавиш
    input.addEventListener('keydown', function(e) {
        // Разрешаем навигационные клавиши
        const navigationKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Tab'];
        if (navigationKeys.includes(e.key)) {
            return;
        }
        
        // Обработка Backspace и Delete
        if (e.key === 'Backspace' || e.key === 'Delete') {
            const cursorPos = this.selectionStart;
            const value = this.value;
            
            // Если курсор после служебного символа, удаляем предыдущую цифру
            if (cursorPos > 0 && cursorPos <= value.length) {
                const prevChar = value[cursorPos - 1];
                // Проверяем, является ли предыдущий символ служебным
                if (prevChar === '+' || prevChar === '(' || prevChar === ')' || prevChar === '-' || prevChar === ' ') {
                    e.preventDefault();
                    
                    // Ищем предыдущую цифру
                    let newPos = cursorPos - 1;
                    let foundDigit = false;
                    
                    while (newPos > 0 && !foundDigit) {
                        newPos--;
                        const char = value[newPos];
                        if (char >= '0' && char <= '9') {
                            foundDigit = true;
                            break;
                        }
                    }
                    
                    if (foundDigit) {
                        // Удаляем цифру
                        const newValue = value.slice(0, newPos) + value.slice(newPos + 1);
                        this.value = newValue;
                        this.setSelectionRange(newPos, newPos);
                        // Триггерим input для переформатирования
                        this.dispatchEvent(new Event('input', { bubbles: true }));
                    } else {
                        // Если цифр нет, очищаем поле
                        this.value = '';
                    }
                }
            }
        }
    });
    
    // Обработка вставки из буфера
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const digits = pastedText.replace(/\D/g, '');
        
        if (digits.length > 0) {
            let processedDigits = digits;
            
            // Обрабатываем разные форматы вставки
            if (processedDigits.length === 10) {
                processedDigits = '7' + processedDigits;
            } else if (processedDigits.length === 11) {
                if (processedDigits.startsWith('8')) {
                    processedDigits = '7' + processedDigits.substring(1);
                } else if (processedDigits.startsWith('7')) {
                    processedDigits = processedDigits;
                } else {
                    processedDigits = '7' + processedDigits;
                }
            }
            
            // Форматируем и устанавливаем значение
            const formatted = formatPhoneNumber(processedDigits);
            this.value = formatted;
            
            // Устанавливаем курсор в конец
            setTimeout(() => {
                this.setSelectionRange(this.value.length, this.value.length);
            }, 0);
            
            // Триггерим событие input
            this.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
    
    // При фокусе, если поле пустое, показываем маску
    input.addEventListener('focus', function() {
        if (this.value === '') {
            this.value = '+7 ';
            setTimeout(() => {
                this.setSelectionRange(3, 3);
            }, 0);
        }
    });
    
    // При потере фокуса, если введено только +7 или мало цифр, очищаем
    input.addEventListener('blur', function() {
        const digits = getDigits(this.value);
        if (digits.length < 2 || digits === '7') {
            this.value = '';
        }
    });
}

// Применяем маску к обоим полям
if (phoneInput) phoneMask(phoneInput);
if (phoneMainInput) phoneMask(phoneMainInput);
    
    // ===== ВАЛИДАЦИЯ ФОРМ =====
    
    function validateModalForm() {
        let isValid = true;
        const name = document.getElementById('name');
        const phone = document.getElementById('phone');
        
        if (!name || !phone) return false;
        
        if (!name.value.trim() || name.value.trim().length < 2) {
            name.classList.add('error');
            isValid = false;
        } else {
            name.classList.remove('error');
        }
        
        const phoneDigits = phone.value.replace(/\D/g, '');
        if (phoneDigits.length < 11) {
            phone.classList.add('error');
            isValid = false;
        } else {
            phone.classList.remove('error');
        }
        
        return isValid;
    }
    
    function validateMainForm() {
        let isValid = true;
        const name = document.getElementById('name_main');
        const phone = document.getElementById('phone_main');
        
        if (!name || !phone) return false;
        
        if (!name.value.trim() || name.value.trim().length < 2) {
            name.classList.add('error');
            isValid = false;
        } else {
            name.classList.remove('error');
        }
        
        const phoneDigits = phone.value.replace(/\D/g, '');
        if (phoneDigits.length < 11) {
            phone.classList.add('error');
            isValid = false;
        } else {
            phone.classList.remove('error');
        }
        
        return isValid;
    }
    
    function showFieldErrors(errors, formType = 'modal') {
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        
        Object.keys(errors).forEach(field => {
            let input;
            if (formType === 'modal') {
                input = document.getElementById(field);
            } else if (formType === 'main') {
                input = document.getElementById(field + '_main');
            }
            
            if (input) {
                input.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = errors[field];
                errorDiv.style.color = '#ff4d4d';
                errorDiv.style.fontSize = '12px';
                errorDiv.style.marginTop = '5px';
                errorDiv.style.marginLeft = '5px';
                
                input.parentNode.appendChild(errorDiv);
            }
        });
    }
    
    // ===== ОБЩАЯ ФУНКЦИЯ ОТПРАВКИ =====
    
    function sendForm(formData, formType, submitBtn, originalText, successCallback) {
        fetch('/api/main.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Ошибка сервера: ' + text);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                successCallback();
            } else {
                if (data.errors) {
                    showFieldErrors(data.errors, formType);
                } else {
                    alert('Ошибка: ' + (data.message || 'Попробуйте позже'));
                }
                if (submitBtn) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при отправке. Пожалуйста, попробуйте позже или позвоните нам.');
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    // ===== ОТПРАВКА МОДАЛЬНОЙ ФОРМЫ =====
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateModalForm()) {
                const submitBtn = document.querySelector('.modal-submit-btn');
                const originalText = submitBtn.textContent;
                submitBtn.innerHTML = '<span class="loading-spinner"></span> Отправка...';
                submitBtn.disabled = true;
                
                const formData = {
                    name: document.getElementById('name').value.trim(),
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email')?.value || '',
                    message: document.getElementById('message')?.value || '',
                    page: window.location.href,
                    form: 'modal'
                };
                
                sendForm(formData, 'modal', submitBtn, originalText, function() {
                    contactForm.style.display = 'none';
                    successMessage.style.display = 'block';
                    
                    setTimeout(() => {
                        closeModal();
                        contactForm.reset();
                        contactForm.style.display = 'flex';
                        successMessage.style.display = 'none';
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                });
            }
        });
    }
    
    // ===== ОТПРАВКА ОСНОВНОЙ ФОРМЫ =====
    
    if (contactMainForm) {
        contactMainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateMainForm()) {
                const submitBtn = document.querySelector('.form_submit_btn');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading-spinner"></span> Отправка...';
                
                const formData = {
                    name: document.getElementById('name_main').value.trim(),
                    phone: document.getElementById('phone_main').value,
                    email: document.getElementById('email_main')?.value || '',
                    message: document.getElementById('message_main')?.value || '',
                    page: window.location.href,
                    form: 'main'
                };
                
                sendForm(formData, 'main', submitBtn, originalText, function() {
                    submitBtn.style.display = 'none';
                    contactMainForm.style.display = 'none';
                    successMessageMain.style.display = 'block';
                    
                    setTimeout(() => {
                        submitBtn.style.display = 'block';
                        contactMainForm.style.display = 'block';
                        successMessageMain.style.display = 'none';
                        contactMainForm.reset();
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 3000);
                });
            }
        });
    }
    
    // ===== УДАЛЕНИЕ СООБЩЕНИЙ ОБ ОШИБКАХ ПРИ ВВОДЕ =====
    
    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('error');
            const errorMsg = this.parentNode.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        });
    });
    
    // ===== ИНИЦИАЛИЗАЦИЯ =====
    
    window.addEventListener('scroll', handleScroll);
    
    setTimeout(() => {
        updateActiveMenuOnScroll();
        handleScroll();
    }, 100);
    
    window.addEventListener('resize', function() {
        updateActiveMenuOnScroll();
    });
    
    if ('ontouchstart' in window) {
        document.body.style.webkitTapHighlightColor = 'transparent';
    }
    
    // Стили для спиннера загрузки (добавляем динамически)
    const style = document.createElement('style');
    style.textContent = `
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        input.error {
            border-color: #ff4d4d !important;
            background-color: #fff8f8 !important;
        }
        
        .error-message {
            font-size: 12px;
            color: #ff4d4d;
            margin-top: 5px;
            margin-left: 5px;
        }
        
        .success-message, .success_message {
            text-align: center;
            padding: 20px;
        }
        
        .success-icon, .success_icon {
            width: 50px;
            height: 50px;
            background-color: #4caf50;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 15px;
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(style);
});