async function Map_prof() {
    //Шлём запрос 
    let res = await fetch ('http://kinoset/Inf_User/' + 1);
    let posts = await res.json();
    document.getElementById('name').textContent += posts.Login;
    document.getElementById('email').textContent += posts.Email;
    document.getElementById('date').textContent += posts.Data;
    document.getElementById('film_1').textContent += posts.Number_views;
    

}
Map_prof()