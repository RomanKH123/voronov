document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы
    const modal = document.getElementById('modal');
    const closeBtn = document.querySelector('.close-modal');
    const contactForm = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');
    const phoneInput = document.getElementById('phone');
    
    // Элементы для формы в контактах (ТВОЯ ОСНОВНАЯ ФОРМА)
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
    
    // Кнопки для открытия модального окна - УБРАЛ 'отправить заявку'
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
    
    // ===== МАСКА ДЛЯ ТЕЛЕФОНА =====
    
    function phoneMask(input) {
        if (!input) return;
        
        input.addEventListener('input', function(e) {
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
    }
    
    phoneMask(phoneInput);
    phoneMask(phoneMainInput);
    
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
                submitBtn.innerHTML = '<span class="loading-spinner"></span>';
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
    
    // ===== ОТПРАВКА ТВОЕЙ ФОРМЫ (contactFormMain) =====
    
    if (contactMainForm) {
        contactMainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateMainForm()) {
                const submitBtn = document.querySelector('.form_submit_btn');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading-spinner"></span>';
                
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
    
    // ===== ДОПОЛНИТЕЛЬНЫЕ ФУНКЦИИ =====
    
    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('error');
            const errorMsg = this.parentNode.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        });
    });
    
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
});