// Функция для плавного скролла
function slowScroll(selector) {
    const element = document.getElementById(selector) || document.querySelector('.' + selector);
    
    if (element) {
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offset = window.innerWidth <= 768 ? 60 : 80;
        const offsetPosition = elementPosition - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
    return false;
}

// Определяем тип устройства
const isTouchDevice = () => {
    return (('ontouchstart' in window) ||
        (navigator.maxTouchPoints > 0) ||
        (navigator.msMaxTouchPoints > 0));
};

// Функции для эффектов наведения на изображения услуг
function chetkaImg(id) {
    const img = document.getElementById(id);
    if (!img) return;
    
   
    
    // Добавляем анимацию
    img.style.transform = 'scale(1.1)';
    img.style.transition = 'transform 0.2s ease';
}

function chetkaImg_1(id) {
    const img = document.getElementById(id);
    if (!img) return;
    
   
    img.style.transform = 'scale(1)';
}

// Исправляем работу details на мобильных
function fixDetailsOnMobile() {
    const details = document.querySelectorAll('details');
    
    details.forEach(detail => {
        // Убираем встроенные обработчики
        detail.removeEventListener('click', handleDetailClick);
        detail.removeEventListener('touchstart', handleDetailTouch);
        
        // Добавляем свои обработчики
        detail.addEventListener('click', handleDetailClick);
        
        if (isTouchDevice()) {
            detail.addEventListener('touchstart', handleDetailTouch, { passive: true });
        }
    });
}

function handleDetailClick(e) {
    const detail = e.currentTarget;
    const summary = detail.querySelector('summary');
    
    // Если клик был не по summary, игнорируем
    if (!e.target.closest('summary')) return;
    
    e.preventDefault();
    
    // Переключаем состояние
    if (detail.hasAttribute('open')) {
        detail.removeAttribute('open');
    } else {
        detail.setAttribute('open', '');
    }
}

function handleDetailTouch(e) {
    const detail = e.currentTarget;
    const summary = detail.querySelector('summary');
    
    // Если тап был по summary
    if (e.target.closest('summary')) {
        e.preventDefault();
        
        // Визуальный отклик
        summary.style.backgroundColor = 'rgba(0,119,255,0.1)';
        summary.style.transition = 'background-color 0.1s ease';
        
        setTimeout(() => {
            summary.style.backgroundColor = 'transparent';
        }, 100);
        
        // Переключаем состояние
        if (detail.hasAttribute('open')) {
            detail.removeAttribute('open');
        } else {
            detail.setAttribute('open', '');
        }
    }
}

// Обработка touch-событий для всех интерактивных элементов
function setupTouchHandlers() {
    // Карточки услуг
    const serviceCards = document.querySelectorAll('.cart_1_plus');
    serviceCards.forEach(card => {
        // Удаляем старые обработчики
        card.removeEventListener('touchstart', handleServiceCardTouch);
        card.removeEventListener('touchend', handleServiceCardTouchEnd);
        card.removeEventListener('touchcancel', handleServiceCardTouchCancel);
        
        // Добавляем новые
        card.addEventListener('touchstart', handleServiceCardTouch, { passive: true });
        card.addEventListener('touchend', handleServiceCardTouchEnd, { passive: true });
        card.addEventListener('touchcancel', handleServiceCardTouchCancel, { passive: true });
    });
    
    // Карточки команды
    const teamCards = document.querySelectorAll('.cart_1_plus_1');
    teamCards.forEach(card => {
        card.removeEventListener('touchstart', handleTeamCardTouch);
        card.removeEventListener('touchend', handleTeamCardTouchEnd);
        card.removeEventListener('touchcancel', handleTeamCardTouchCancel);
        
        card.addEventListener('touchstart', handleTeamCardTouch, { passive: true });
        card.addEventListener('touchend', handleTeamCardTouchEnd, { passive: true });
        card.addEventListener('touchcancel', handleTeamCardTouchCancel, { passive: true });
    });
    
    // Кнопка контактов
    const contactBtn = document.querySelector('.contact_button');
    if (contactBtn) {
        contactBtn.removeEventListener('touchstart', handleContactBtnTouch);
        contactBtn.removeEventListener('touchend', handleContactBtnTouchEnd);
        contactBtn.removeEventListener('touchcancel', handleContactBtnTouchCancel);
        
        contactBtn.addEventListener('touchstart', handleContactBtnTouch, { passive: true });
        contactBtn.addEventListener('touchend', handleContactBtnTouchEnd, { passive: true });
        contactBtn.addEventListener('touchcancel', handleContactBtnTouchCancel, { passive: true });
    }
    
    // Этапы работы
    const stages = document.querySelectorAll('.vopros_plus_1, .vopros_plus_2');
    stages.forEach(stage => {
        stage.removeEventListener('touchstart', handleStageTouch);
        stage.removeEventListener('touchend', handleStageTouchEnd);
        stage.removeEventListener('touchcancel', handleStageTouchCancel);
        
        stage.addEventListener('touchstart', handleStageTouch, { passive: true });
        stage.addEventListener('touchend', handleStageTouchEnd, { passive: true });
        stage.addEventListener('touchcancel', handleStageTouchCancel, { passive: true });
    });
}

// Обработчики для карточек услуг
function handleServiceCardTouch(e) {
    const card = e.currentTarget;
    
    // Анимация нажатия
    card.style.transform = 'scale(0.97)';
    card.style.transition = 'transform 0.1s ease';
    card.style.backgroundColor = 'rgba(0,119,255,0.05)';
    
    // Меняем изображение
    const img = card.querySelector('img');
    if (img && img.id) {
        chetkaImg(img.id);
    }
}

function handleServiceCardTouchEnd(e) {
    const card = e.currentTarget;
    
    // Возвращаем в исходное состояние
    card.style.transform = 'scale(1)';
    card.style.backgroundColor = 'white';
    
    // Возвращаем изображение
    const img = card.querySelector('img');
    if (img && img.id) {
        chetkaImg_1(img.id);
    }
}

function handleServiceCardTouchCancel(e) {
    const card = e.currentTarget;
    
    // Отмена касания
    card.style.transform = 'scale(1)';
    card.style.backgroundColor = 'white';
    
    const img = card.querySelector('img');
    if (img && img.id) {
        chetkaImg_1(img.id);
    }
}

// Обработчики для карточек команды
function handleTeamCardTouch(e) {
    const card = e.currentTarget;
    card.style.transform = 'scale(0.97)';
    card.style.transition = 'transform 0.1s ease';
    card.style.backgroundColor = 'rgba(0,119,255,0.05)';
}

function handleTeamCardTouchEnd(e) {
    const card = e.currentTarget;
    card.style.transform = 'scale(1)';
    card.style.backgroundColor = 'white';
}

function handleTeamCardTouchCancel(e) {
    const card = e.currentTarget;
    card.style.transform = 'scale(1)';
    card.style.backgroundColor = 'white';
}

// Обработчики для кнопки контактов
function handleContactBtnTouch(e) {
    const btn = e.currentTarget;
    btn.style.transform = 'scale(0.95)';
    btn.style.transition = 'transform 0.1s ease';
    btn.style.backgroundColor = '#0055cc';
}

function handleContactBtnTouchEnd(e) {
    const btn = e.currentTarget;
    btn.style.transform = 'scale(1)';
    btn.style.backgroundColor = '#0077ff';
}

function handleContactBtnTouchCancel(e) {
    const btn = e.currentTarget;
    btn.style.transform = 'scale(1)';
    btn.style.backgroundColor = '#0077ff';
}

// Обработчики для этапов работы
function handleStageTouch(e) {
    const stage = e.currentTarget;
    stage.style.transform = 'scale(0.99) translateX(5px)';
    stage.style.transition = 'transform 0.1s ease';
    stage.style.backgroundColor = 'rgba(0,119,255,0.05)';
}

function handleStageTouchEnd(e) {
    const stage = e.currentTarget;
    stage.style.transform = 'scale(1) translateX(0)';
    stage.style.backgroundColor = 'white';
}

function handleStageTouchCancel(e) {
    const stage = e.currentTarget;
    stage.style.transform = 'scale(1) translateX(0)';
    stage.style.backgroundColor = 'white';
}

// Липкое меню при скролле
window.addEventListener('scroll', function() {
    const stickyMenu = document.getElementById('stickyMenu');
    const header = document.querySelector('.logo');
    
    if (stickyMenu && header) {
        const threshold = window.innerWidth <= 768 ? 0.3 : 0.8;
        if (window.scrollY > header.offsetHeight * threshold) {
            stickyMenu.classList.add('active');
        } else {
            stickyMenu.classList.remove('active');
        }
    }
}, { passive: true });

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Исправляем details
    fixDetailsOnMobile();
    
    // Добавляем плавный скролл для всех ссылок
    const allLinks = document.querySelectorAll('a[href^="#"]');
    allLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const selector = this.getAttribute('href').substring(1);
            slowScroll(selector);
        });
    });
    
    // Обработка для ссылок с onclick
    const linksWithOnClick = document.querySelectorAll('[onclick*="slowScroll"]');
    linksWithOnClick.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const match = this.getAttribute('onclick').match(/'([^']+)'/);
            if (match) {
                slowScroll(match[1]);
            }
        });
    });
    
    // Настраиваем touch-обработчики
    setupTouchHandlers();
    
    // Эффект появления элементов при скролле
    const observerOptions = {
        threshold: window.innerWidth <= 768 ? 0.05 : 0.1,
        rootMargin: '0px 0px -20px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Добавляем анимацию для карточек
    document.querySelectorAll('.cart_1_plus, .cart_1_plus_1, .vopros_plus_1, .vopros_plus_2, .vopros_2_pl').forEach(el => {
        el.style.opacity = '1'; // Убираем начальную анимацию, которая мешает
    });
    
    // Предотвращаем двойное нажатие для zoom
    if (isTouchDevice()) {
        document.addEventListener('touchstart', (e) => {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });
    }
});

// Повторная инициализация после загрузки изображений
window.addEventListener('load', function() {
    fixDetailsOnMobile();
    setupTouchHandlers();
});