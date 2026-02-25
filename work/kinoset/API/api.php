<?php
    //Определяем типы вывода данных (Json) и права отправки
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    header('Access-Control-Allow-Methods: *');
    header('Access-Control-Allow-Credentials: true' );
    header('Content-type: json/application');
    
    //Подключаем старонние пакеты
    #Подключение к БД
    require "../DataBase/connect.php";
    #Подключение к функциям
    require "./function.php";
    //Читаем запрос    
    $params = explode('/',$_GET['q']);
    //Определяем вид и категорию запроса
    #Вид запроса/категория
    switch([$_SERVER['REQUEST_METHOD'],$params[0]]){
        #Отправка по GET запросу
        //Вывод  для главных страниц
        case ['GET','posts']:           
            #Подключение,раздел,жанрфильма(необязательно)/id пользывателя
            GetFilms($connect,$params[1],$params[2]);              
        break;
        //Вывод  для плеера
        case ['GET','pleer']:           
            #Подключение,ID
            PleerFilms($connect,$params[1]);              
        break;
        //Распаковка пользователя
        case ['GET','User_up']:
            User_up($connect,$params[1]);
        break;
        //Выводим данные пользователя
            #подключение/ID
        case ['GET','Inf_User']:
            InfUser($connect,$params[1]);
        break;
        //Записываем историю просмотра
        case ['POST','history_user']:
            
            //Декодируем `POST`
            $_POSTS = json_decode(file_get_contents("php://input"), true);   
            #Подключение,ID фильма и пользывателя
            HistorUser($connect,$_POSTS);       
            
        break;
        //Отчищаем историю просмотра
        case ['POST','clear_history']:
            //Декодируем `POST`
            $_POSTS = json_decode(file_get_contents("php://input"), true);    
            #Подключение,тип операции
            ClearHistory($connect,$_POSTS);       
            
        break;
    }
    
?>