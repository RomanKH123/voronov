<?php
require '../DataBase/connect.php'; // Подключение к БД
$par_type = $_POST['type'];
switch ($par_type){
    //Вход в систему
    case "log":
        //Получаем логин и пароль
        $login = $_POST['login']; $passwordd = $_POST['password'];
        //Проверяем логин и пароль
        $posts = mysqli_query($connect, "SELECT `ID_user`, `Login`, `Pussword`FROM `User` WHERE `Login` = '$login' AND `Pussword` = '$passwordd'");
        $row = mysqli_fetch_array($posts);
        $id = $row['ID_user'];
        //Обрабатываем ошибку
        if(mysqli_num_rows($posts) === 0){
            header("Location: ../Web/Pages/eror.html");
        //Вход    
        }else{
            header("Location: ../index.html?id=$id");
        }
        
    break;
    case "reg":
        session_start();
        //Получаем логин и пароль
        $login = $_POST['login']; $passwordd = $_POST['password'];
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            //получаем почту
            $email = $_POST['email'];

            $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Хешируем пароль
            $code = rand(100000, 999999); // Генерация кода подтверждения

            // Сохранение кода во временную таблицу (можно использовать Redis, если доступен)
            $stmt = $pdo->prepare("INSERT INTO verification_codes (email, code, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$email, $code]);

            // Отправка кода на email
            mail($email, "Код подтверждения", "Ваш код подтверждения: $code");

            $_SESSION['email'] = $email;
            $_SESSION['password'] = $password; // Временное сохранение пароля
            header("Location: ../Web/Pages/logout.php?login=$login&pasword=$passwordd");
            exit();
        }
        break;
            
        }

?>