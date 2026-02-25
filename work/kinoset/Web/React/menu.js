function deletDiv(id){
    document.getElementById(id).innerHTML = "";
}
//Обрабатываем меню пользователя
async function menu(a) {
    switch(a){
        //Вход выполнен
        case 'log':
            document.getElementById('logo').src = './img/avotar_log.png'
            deletDiv('menu');     
            document.getElementById('menu').innerHTML +=
            `
            <a>${localStorage.getItem('Logina2f')}</a>
            <a href="./Web/Pages/profabout.html">Профиль</a>
            <a href="./Web/Pages/about.html">О нас</a>
            <a onclick="ExitProf()">Выйти</a>  
        
            `
        break;
        //Вход не выполнен
        case 'nlog':
            deletDiv('menu');     
            document.getElementById('menu').innerHTML +=
            `
            <a href="/Web/Pages/logout.html">Войти</a>
            <a href="./Web/Pages/about.html">О нас</a>  
        
            `
        break;
    }    
}

let stat
//Проверяем вход в систему
if (localStorage.getItem('Logina2f') === null){
    stat = "nlog";
}else{stat = "log"; let avatar = localStorage.getItem('Logina2f');}

menu(stat);