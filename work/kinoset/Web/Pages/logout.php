<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel = "stylesheet" href = "/Web/Style/logout.css">   
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <title>Вход</title>
    
    
</head>
<body>
    <div class = "logout_blok">
        
        <div class="logout_form">
        <form method="post" action="/Server/logout_verefy.php">
                <input style="display: none;" value="reg" name="type">
                <div class = "logout_mail">Мы отправили код подтверждения на вашу почту, введите его</div>
                <input class="logout_form" type = "text" name = "pusword_email">
                <button class="logout_form">Далее</button>
        </form>
        </div>
    </div>
</body>

</html>