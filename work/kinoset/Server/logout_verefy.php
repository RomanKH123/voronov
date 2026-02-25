<?php
session_start();
require 'db.php';
//Проверяем почту
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Собираем данные
    $email = $_SESSION['email'];
    $password = $_SESSION['password'];
    $code = $_POST['code'];

    // Проверка кода
    $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE email = ? AND code = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)");
    $stmt->execute([$email, $code]);

    if ($stmt->rowCount() > 0) {
        // Добавление пользователя в БД
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $password]);

        // Удаляем код подтверждения
        $pdo->prepare("DELETE FROM verification_codes WHERE email = ?")->execute([$email]);

        echo "Регистрация успешна!";
        session_destroy();
    } else {
        echo "Неверный код!";
    }
}
?>