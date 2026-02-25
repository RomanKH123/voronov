//Записываем просмотр в историю
export async function HistorUser(ID_film) {
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