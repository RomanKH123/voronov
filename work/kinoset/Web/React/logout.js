//Выводим окно для входа
async function wind_vh() {
    document.getElementById("form_root").innerHTML = ""
    document.getElementById("form_root").innerHTML += `
         <form method="post" action="/Server/logout_reg.php">
                <input style="display: none;" value="log" name="type">
                <div class = "logout_txt">Логин</div>
                <input class="logout_form" type = "text" name = "login">
                <div class = "logout_txt">Пароль</div>
                <input class="logout_form" type = "password" name = "password">
                <button class="logout_form">Войти</button>
                <a class="logout_form" href = "/index.html">Назад</a>
        </form>
    `
}
//Выводим окно для регистрации
async function wind_rg() {
    document.getElementById("form_root").innerHTML = ""
    document.getElementById("form_root").innerHTML += `
       <form method="post" action="/Web/Pages/logout.php" name="type">
                <div class = "logout_txt">Логин</div>
                <input class="logout_form" type = "text" name = "login">
                <div class = "logout_txt">Почта</div>
                <input class="logout_form" type = "email" name = "email">
                <div class = "logout_txt">Пароль</div>
                <input class="logout_form" type = "password" name = "password">
                <button class="logout_form">Войти</button>
                <a class="logout_form" href = "/index.html">Назад</a>
            </form>
    `
}
wind_vh()