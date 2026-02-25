
async function clear_history() {
  try {
      const res_clear = await fetch('http://kinoset/clear_history', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json'
          },
          body: JSON.stringify({ teg: "log", user: localStorage.getItem('Idsdw') })
      });

      // Проверяем статус ответа
      if (!res_clear.ok) {
          throw new Error(`Ошибка HTTP: ${res_clear.status}`);
      }

      // Проверяем, есть ли тело ответа
      const text = await res_clear.text();
      const data = text ? JSON.parse(text) : {};  // Если тело пустое, парсим пустой объект

      console.log("Success:", data);
      Map_history();
  } catch (error) {
      console.error("Ошибка при очистке истории:", error);
  }
}


//Функция для чистки блоков
function deletDiv(id){
  document.getElementById(id).innerHTML = "";
}
//Вывод для главной страницы
async function Map_main() {
    //Шлём запрос 
    let res = await fetch ('http://kinoset/posts/1');
    let posts = await res.json();

    //Чистим блоки
    deletDiv('single');
    deletDiv('root');
    deletDiv('dop_root');
         
    posts.forEach(post => {
        //Выводим результаты главной страницы
        document.getElementById('root').innerHTML +=
        `
        <div class="film" id = ${post.ID_film_stak} name = ${post.Name_film} onclick='javascript: document.location.href = "./Web/Pages/pleer.html?id=${post.ID_film_stak}"'>
            <img src = ${post.Screen}/>
        </div>
       
        `
    })
}
//Выводим свежие фильмы
async function Map_new() {
    //Шлём запрос 
    let res = await fetch ('http://kinoset/posts/2');
    let posts = await res.json();
    //Чистим блоки
    deletDiv('single');
    deletDiv('root');
    deletDiv('dop_root');   
    posts.forEach(post => {
        document.getElementById('root').innerHTML +=
        //Выводим новые фильмы
        `
        <div class="film" id = ${post.ID_film_stak} name = ${post.Name_film} onclick='javascript: document.location.href = "./Web/Pages/pleer.html?id=${post.ID_film_stak}"';>
            <img src = ${post.Screen}/>
        </div>
        
        `
    })
}
//Выводим фильмы по жанрам
async function Map_chek(title) {
    //Отправка гета 
    let res = await fetch ('http://kinoset/posts/3' + '/' + title);
    let posts = await res.json();
    //Распаковка гета
        
    deletDiv('root');
    deletDiv('dop_root');
    posts.forEach(post => {
        document.getElementById('root').innerHTML +=
        //Распоковываем фильмы по жанрам
        `
        <div class="film" id = ${post.ID_film_stak} name = ${post.Name_film} onclick='javascript: document.location.href = "./Web/Pages/pleer.html?id=${post.ID_film_stak}"'>
            <img src = ${post.Screen}/>
        </div>
        
        `
    })
        
}
//Доп стили для выбора жанров
function dop_style(){
    deletDiv('single');
     
    //Подгружаем выбор жанров
    document.getElementById('single').innerHTML += `
    <div class="container swiper">
        <div class="slider-wrapper">
          <div class="card-list swiper-wrapper">
            <div class="card-item swiper-slide , boeviki" onclick="Map_chek('Боевик')">
              Боевики
            </div>    
            <div class="card-item swiper-slide , detectiv"  onclick="Map_chek('Детектив')">
              Детективы
            </div>    
            <div class="card-item swiper-slide , komedy"  onclick="Map_chek('Комедия')">
              Комедии
            </div>    
            <div class="card-item swiper-slide , anime"  onclick="Map_chek('Аниме')">
             Аниме
            </div>            
            <div class="card-item swiper-slide , horor"  onclick="Map_chek('Хороры')">
              Хороры
            </div>    
            <div class="card-item swiper-slide , history"  onclick="Map_chek('Историческое')">
                Исторические
            </div>
            <div class="card-item swiper-slide , fentezi"  onclick="Map_chek('Фентези')">
                Фентези
            </div>
            <div class="card-item swiper-slide , multfilm"  onclick="Map_chek('Мультфильм')">
              Мультфильмы
          </div>
          </div>
    
          <div class="swiper-pagination"></div>
          <div class="swiper-slide-button swiper-button-prev"></div>
          <div class="swiper-slide-button swiper-button-next"></div>
        </div>
      </div>     
      
    `
    //Доп стили для выбора жанров
    const swiper = new Swiper('.slider-wrapper', {
      loop: true,
      grabCursor: true,
      spaceBetween: 30,
    
      // Pagination bullets
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
        dynamicBullets: true
      },
    
      // Navigation arrows
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    
      // Responsive breakpoints
      breakpoints: {
        0: {
          slidesPerView: 1
        },
        768: {
          slidesPerView: 2
        },
        1024: {
          slidesPerView: 3
        }
      }
    });
    
  }
//Отображение истории запросов(Не доделано)
async function Map_history() {
  posts = [];
  //Чистим прошлые записи
  deletDiv('single');
  deletDiv('root');
  deletDiv('dop_root');
  
  //Проверяем вход пользывателя
  if (localStorage.getItem('Logina2f') === null){
    document.getElementById('root').innerHTML +=
        `
          <div class="kat_eror"><img src = "/img/kat_eror.png"></div>
          <div class="er_txt">Похоже вы не вошли в свой профиль</div>
        
        `
  }else{
    
    let res = await fetch ('http://kinoset/posts/4/' + localStorage.getItem('Idsdw'));
    let posts = await res.json();
    //Распаковка гета

    document.getElementById('dop_root').innerHTML +=
        `
        <div class = "clear_history" onclick="clear_history()">Отчистить</div>
        `
    posts.forEach(post => {
        //Выводим результаты главной страницы
        document.getElementById('root').innerHTML +=
        `
        <div class="film" id = ${post.ID_film} name = ${post.Name_film} onclick='javascript: document.location.href = "./Web/Pages/pleer.html?id=${post.ID_film}"'>
            <img src = ${post.Screen}/>
        </div>
       
        `
    })
       
  }     

}

Map_main()
