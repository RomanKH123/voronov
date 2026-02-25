//Записываем просмотр в историю
async function HistorUser(ID_film) {
    //Готовим пост на отправку    
    const res = await fetch('http://kinoset/history_user', {
        method: 'POST',
        //Присваиваем тип JSON к запросу
        headers: {
            'Content-Type': 'application/json'
        },
        //Передаём ID фильма и пользывателя в JSON
        body: JSON.stringify({ film: ID_film, user: localStorage.getItem('Idsdw') })
    });
    //Выводим результат
    console.log("Success:", await res.json()); 


}
//Выводим фильм
async function Pleer_main(a) {
    let res = await fetch ('http://kinoset/pleer' + '/'+ a);
    let posts = await res.json();    
    document.getElementById('root').innerHTML = "";      
    
    
    //Выводим название фильма
    document.getElementById('name').textContent = posts.Name_film;
    document.querySelector('.film_name').textContent = posts.Name_film;
    HistorUser(posts.ID_film_stak)
    //Выводим плеер и описание
    document.getElementById('root').innerHTML +=
    
        `
        <iframe class = "player" src="${posts.Link}" frameBorder="0"  webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
        <div class = "description">
           <div>Рейтинг: ${posts.Estimation_1/posts.Estimation_2}</div>
           <div>Дата выхода : ${posts.Data.split("-").reverse().join("-")} </div>
        </div>
        <div class = "description">
            <div>${posts.Description}</div>
        </div>
        `  
        
        
}
// Получаем параметры из текущего URL
const urlParams = new URLSearchParams(window.location.search);
const id = urlParams.get('id');  // Получаем значение параметра "id"
let link ='http://kinoset/pleer' + '/'+ id;
Pleer_main(id)