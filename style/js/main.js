document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы
    const modal = document.getElementById('modal');
    const closeBtn = document.querySelector('.close-modal');
    const contactForm = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');
    const phoneInput = document.getElementById('phone');
    
    // Элементы для новой формы в контактах
    const contactMainForm = document.getElementById('contactFormMain');
    const successMessageMain = document.getElementById('successMessageMain');
    const phoneMainInput = document.getElementById('phone_main');
    
    // ===== МОДАЛЬНОЕ ОКНО =====
    
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
    
    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
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
    
    // Кнопки для открытия модального окна
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
    
    function showFieldErrors(errors) {
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        
        Object.keys(errors).forEach(field => {
            const input = document.getElementById(field) || document.getElementById(field + '_main');
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
                    page: window.location.href,
                    form: 'modal'
                };
                
                // ИСПРАВЛЕНО: правильный путь к API
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
                            throw new Error('Ошибка сервера: ' + text.substring(0, 100));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        contactForm.style.display = 'none';
                        successMessage.style.display = 'block';
                        
                        setTimeout(() => {
                            closeModal();
                            contactForm.reset();
                            contactForm.style.display = 'flex';
                            successMessage.style.display = 'none';
                        }, 3000);
                    } else {
                        if (data.errors) {
                            showFieldErrors(data.errors);
                        } else {
                            alert('Ошибка: ' + (data.message || 'Попробуйте позже'));
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при отправке: ' + error.message);
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });
    }
    
    // ===== ОТПРАВКА ОСНОВНОЙ ФОРМЫ В КОНТАКТАХ =====
    
    if (contactMainForm) {
        contactMainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateMainForm()) {
                const submitBtn = this.querySelector('.form_submit_btn');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                const formData = {
                    name: document.getElementById('name_main').value.trim(),
                    phone: document.getElementById('phone_main').value,
                    email: document.getElementById('email_main')?.value || '',
                    message: document.getElementById('message_main')?.value || '',
                    page: window.location.href,
                    form: 'main'
                };
                
                // ИСПРАВЛЕНО: правильный путь к API
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
                            throw new Error('Ошибка сервера: ' + text.substring(0, 100));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        submitBtn.classList.remove('loading');
                        submitBtn.style.display = 'none';
                        contactMainForm.style.display = 'none';
                        
                        if (successMessageMain) {
                            successMessageMain.style.display = 'block';
                        }
                        
                        setTimeout(() => {
                            submitBtn.style.display = 'block';
                            contactMainForm.style.display = 'block';
                            if (successMessageMain) {
                                successMessageMain.style.display = 'none';
                            }
                            contactMainForm.reset();
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }, 5000);
                    } else {
                        if (data.errors) {
                            showFieldErrors(data.errors);
                        } else {
                            alert('Ошибка: ' + (data.message || 'Попробуйте позже'));
                        }
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при отправке: ' + error.message);
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
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
    
    window.addEventListener('scroll', updateActiveMenuOnScroll);
    
    setTimeout(() => {
        updateActiveMenuOnScroll();
    }, 100);
});