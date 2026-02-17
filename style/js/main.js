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
        document.body.style.overflow = 'hidden';
        contactForm.style.display = 'flex';
        successMessage.style.display = 'none';
        contactForm.reset();
        document.querySelectorAll('.form-group input').forEach(input => {
            input.classList.remove('error');
        });
    }
    
    // Функция для закрытия модального окна
    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    // Функция для плавной прокрутки к блоку
    window.scrollToBlock = function(blockId) {
        const element = document.getElementById(blockId);
        if (element) {
            const menuHeight = document.querySelector('.menu_st').offsetHeight;
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - menuHeight - 20;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
            
            highlightActiveMenuItem(blockId);
        }
    }
    
    // Функция подсветки активного пункта меню
    function highlightActiveMenuItem(activeId) {
        document.querySelectorAll('.menu_st div').forEach(item => {
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
            const menuItems = document.querySelectorAll('.menu_st div');
            if (menuItems[menuIndex]) {
                menuItems[menuIndex].classList.add('active');
            }
        }
    }
    
    // Отслеживание прокрутки для подсветки активного пункта
    function updateActiveMenuOnScroll() {
        const sections = [
            { id: 'about', menuIndex: 0 },
            { id: 'services', menuIndex: 1 },
            { id: 'works', menuIndex: 2 },
            { id: 'contacts', menuIndex: 3 }
        ];
        
        const menuHeight = document.querySelector('.menu_st').offsetHeight;
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
            document.querySelectorAll('.menu_st div').forEach(item => {
                item.classList.remove('active');
            });
            
            const menuItems = document.querySelectorAll('.menu_st div');
            if (menuItems[currentSection.menuIndex]) {
                menuItems[currentSection.menuIndex].classList.add('active');
            }
        }
    }
    
    // Находим все кнопки и добавляем им обработчик
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        const buttonText = button.textContent.trim().toLowerCase();
        if (buttonText === 'подробнее' || buttonText === 'обсудить проект') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
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
        
        if (name.value.trim().length < 2) {
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
    
    // Отправка формы
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            const submitBtn = document.querySelector('.modal-submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="loading-spinner"></span>';
            submitBtn.disabled = true;
            
            const formData = {
                name: document.getElementById('name').value.trim(),
                phone: document.getElementById('phone').value,
                timestamp: new Date().toISOString(),
                page: window.location.href
            };
            
            // Отправка на сервер
            fetch('/api/main.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contactForm.style.display = 'none';
                    successMessage.style.display = 'block';
                    
                    setTimeout(() => {
                        closeModal();
                    }, 3000);
                } else {
                    alert('Ошибка: ' + (data.message || 'Попробуйте позже'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
    });
    
    // Добавляем отслеживание прокрутки
    window.addEventListener('scroll', updateActiveMenuOnScroll);
    
    // Подсвечиваем первый пункт при загрузке
    setTimeout(() => {
        updateActiveMenuOnScroll();
    }, 100);
});