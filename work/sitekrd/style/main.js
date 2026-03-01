

function chetkaImg(a){
  if (document.getElementById(a).getAttribute('src') == "img/logo/dizaine.png"){
    document.getElementById("chekId_1").src = "img/logo/v2/dizaine.png"; 
  } 
  if (document.getElementById(a).getAttribute('src') == "img/logo/site.png"){
    document.getElementById("chekId_2").src = "img/logo/v2/site.png";
  } 
  if (document.getElementById(a).getAttribute('src') == "img/logo/host.png"){
    document.getElementById("chekId_3").src = "img/logo/v2/host.png";
  } 
  if (document.getElementById(a).getAttribute('src') == "img/logo/texr.png"){
    document.getElementById("chekId_4").src = "img/logo/v2/terx.png";
  }
}

function chetkaImg_1(a){
  if (document.getElementById(a).getAttribute('src') == "img/logo/v2/dizaine.png"){
    document.getElementById("chekId_1").src = "img/logo/dizaine.png"; 
  } 
  if (document.getElementById(a).getAttribute('src') == "img/logo/v2/site.png"){
    document.getElementById("chekId_2").src = "img/logo/site.png";
  } 
  if (document.getElementById(a).getAttribute('src') == "img/logo/v2/host.png"){
    document.getElementById("chekId_3").src = "img/logo/host.png";
  } 
  if (document.getElementById(a).getAttribute('src') == "img/logo/v2/terx.png"){
    document.getElementById("chekId_4").src = "img/logo/texr.png";
  }
}
// Функция для плавного скролла
function slowScroll(selector) {
    // Пробуем найти элемент по ID или по классу
    const element = document.getElementById(selector) || document.querySelector('.' + selector);
    
    if (element) {
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - 70; // Отступ для учета липкого меню
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    } else {
        console.log('Элемент не найден:', selector);
    }
    return false;
}

// Функции для эффектов наведения
function chetkaImg(id) {
    const img = document.getElementById(id);
    if (img) {
        img.style.filter = 'brightness(1.2)';
        img.style.transition = '0.3s';
    }
}

function chetkaImg_1(id) {
    const img = document.getElementById(id);
    if (img) {
        img.style.filter = 'brightness(1)';
        img.style.transition = '0.3s';
    }
}

// Липкое меню при скролле
window.addEventListener('scroll', function() {
    const stickyMenu = document.getElementById('stickyMenu');
    const header = document.querySelector('.logo');
    
    if (stickyMenu && header) {
        // Показываем меню когда проскроллили шапку
        if (window.scrollY > header.offsetHeight) {
            stickyMenu.classList.add('active');
        } else {
            stickyMenu.classList.remove('active');
        }
    }
});

// Добавляем обработчики для всех ссылок в липком меню
document.addEventListener('DOMContentLoaded', function() {
    const stickyLinks = document.querySelectorAll('#stickyMenu a');
    stickyLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const selector = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            slowScroll(selector);
        });
    });
});