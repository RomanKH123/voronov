document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы
    const modal = document.getElementById('modal');
    const closeBtn = document.querySelector('.close-modal');
    const contactForm = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');
    const phoneInput = document.getElementById('phone');
    
    // Функция для открытия модального окна
    function openModal() {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Запрещаем прокрутку страницы
        // Сбрасываем форму
        contactForm.style.display = 'flex';
        successMessage.style.display = 'none';
        contactForm.reset();
        // Убираем классы ошибок
        document.querySelectorAll('.form-group input').forEach(input => {
            input.classList.remove('error');
        });
    }
    
    // Функция для закрытия модального окна
    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = ''; // Возвращаем прокрутку
    }
    
    // Находим все кнопки и добавляем им обработчик
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        // Проверяем, что это нужные кнопки (Подробнее или Обсудить проект)
        const buttonText = button.textContent.trim().toLowerCase();
        if (buttonText === 'подробнее' || buttonText === 'обсудить проект') {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Предотвращаем возможную отправку формы
                openModal();
            });
        }
    });
    
    // Закрытие по крестику
    closeBtn.addEventListener('click', closeModal);
    
    // Закрытие по клику вне модального окна
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
    
    // Маска для телефона
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';
        
        if (value.length > 0) {
            if (value.startsWith('7')) {
                formattedValue = '+7 ';
            } else if (value.startsWith('8')) {
                formattedValue = '+7 ';
                value = value.substring(1);
            } else {
                formattedValue = '+7 ';
            }
            
            if (value.length > 1) {
                formattedValue += '(' + value.substring(1, 4);
            }
            if (value.length >= 4) {
                formattedValue += ') ' + value.substring(4, 7);
            }
            if (value.length >= 7) {
                formattedValue += '-' + value.substring(7, 9);
            }
            if (value.length >= 9) {
                formattedValue += '-' + value.substring(9, 11);
            }
        }
        
        e.target.value = formattedValue;
    });
    
    // Валидация формы
    function validateForm() {
        let isValid = true;
        const name = document.getElementById('name');
        const phone = document.getElementById('phone');
        
        // Проверка имени
        if (name.value.trim().length < 2) {
            name.classList.add('error');
            isValid = false;
        } else {
            name.classList.remove('error');
        }
        
        // Проверка телефона
        const phoneDigits = phone.value.replace(/\D/g, '');
        if (phoneDigits.length < 11) {
            phone.classList.add('error');
            isValid = false;
        } else {
            phone.classList.remove('error');
        }
        
        return isValid;
    }
    
    // Отправка формы
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            // Показываем загрузку
            const submitBtn = document.querySelector('.modal-submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="loading-spinner"></span>';
            submitBtn.disabled = true;
            
            // Собираем данные
            const formData = {
                name: document.getElementById('name').value.trim(),
                phone: document.getElementById('phone').value,
                timestamp: new Date().toISOString(),
                page: window.location.href
            };
            
            // Имитация отправки на сервер (замените на ваш реальный URL)
            setTimeout(() => {
                // Скрываем форму и показываем сообщение об успехе
                contactForm.style.display = 'none';
                successMessage.style.display = 'block';
                
                // Восстанавливаем кнопку
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Закрываем модальное окно через 3 секунды
                setTimeout(() => {
                    closeModal();
                }, 3000);
                
                // Здесь можно добавить реальную отправку данных
                console.log('Отправка данных:', formData);
                fetch('/api/main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
            }, 1500);
        }
    });
});