function slowScroll(id){
    $("html, body").animate({
        scrollTop: $(id).offset().top
    }, 300);
    return false;
}

// Кнопка наверх
const scrollTop = document.getElementById('scrollTop');

window.addEventListener('scroll', () => {
    if (window.scrollY > 500) {
        scrollTop.classList.add('show');
    } else {
        scrollTop.classList.remove('show');
    }
});

if (scrollTop) {
    scrollTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Анимации при скролле
function onEntry(entry) {
    entry.forEach(change => {
        if (change.isIntersecting) {
            change.target.classList.add('element-show');
        }
    });
}

let options = {
    threshold: [0.3]
};
let observer = new IntersectionObserver(onEntry, options);
let elements = document.querySelectorAll('.shap_const_1, .blok-time, .blok-time-vs, .service-card, .schedule-card');

for (let elm of elements) {
    observer.observe(elm);
}

// Подсветка активного пункта меню
const sections = document.querySelectorAll('section');
const navLinks = document.querySelectorAll('.nav-link');

function updateActiveLink() {
    let current = '';
    const scrollY = window.scrollY + 100;
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute('id');
        
        if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
            current = sectionId;
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
            link.classList.add('active');
        }
    });
}

window.addEventListener('scroll', updateActiveLink);
window.addEventListener('load', updateActiveLink);