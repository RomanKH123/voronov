<?php
    #Отправка для GET запроса
    //Вывод для главных страниц
    #Подключение БД,тип запроса,дополнительный параметр
    function GetFilms($connect,$type,$dop_params){
        switch($type){
            // Данные для главной страници
            case 1:
                //Вытягиваем все фильмы по добавлению
                $posts = mysqli_query($connect, "SELECT `ID_film_stak`,`Screen`,`Name_film` 
                                        FROM `Film_stak` ORDER BY `ID_film_stak` DESC");
                $postslist = [];
                #Обработка 404
                if(mysqli_num_rows($posts) === 0){
                    http_response_code(404);
                    $res = ["status" => false, "massage" => "Post not found"];
                    echo json_encode($res);
                } else{
                    while ($post = mysqli_fetch_assoc($posts)){
                        $postslist[] = $post;
                    }
                    #Отправка даных через json
                    echo json_encode($postslist);
                
                }
                break;
            // Данные для страницы новинки                
            case 2:
                //Фильмы по дате создания
                $posts = mysqli_query($connect, "SELECT `ID_film_stak`, `Screen`, `Data` ,`Name_film`
                                            FROM `Film_stak` ORDER BY `Data` DESC");
                $postslist = [];
                #Обработка 404
                if(mysqli_num_rows($posts) === 0){
                    http_response_code(404);
                    $res = ["status" => false, "massage" => "Post not found"];
                    echo json_encode($res);
                } else{
                    while ($post = mysqli_fetch_assoc($posts)){
                        $postslist[] = $post;
                    }
                    #Отправка даных через json
                    echo json_encode($postslist);
                    }
                break;
            # Данные для страницы жанры
            case 3:
                //Отборка по жанрам
                $posts = mysqli_query($connect, "SELECT `ID_film_stak`, `Screen`, `Status` ,`Name_film`
                                            FROM `Film_stak` WHERE `Status` = '$dop_params' ORDER BY `ID_film_stak` DESC");
                $postslist = [];
                #Обработка 404
                if(mysqli_num_rows($posts) === 0){
                    http_response_code(404);
                    $res = ["status" => false,"massage" => "Post not found"];
                    echo json_encode($res);
                } else{
                    while ($post = mysqli_fetch_assoc($posts)){
                        $postslist[] = $post;
                    }
                    #Отправка даных через json
                    echo json_encode($postslist);
                    }
            break;
            #Данные для истории просмотров
            case 4:
            //Выводим последнии просмотры пользователя
                $posts = mysqli_query($connect, "SELECT Hi.ID_film_stak AS ID_film, Fi.Screen AS Screen, Hi.ID_user , Fi.Name_film AS Name_film , Hi.Data
                                            FROM History_user Hi JOIN Film_stak Fi 
                                            ON Hi.ID_film_stak = Fi.ID_film_stak 
                                            WHERE Hi.ID_user = '$dop_params' ORDER BY Hi.Data DESC");
                $postslist = [];
                #Обработка 404
                if(mysqli_num_rows($posts) === 0){
                    http_response_code(404);
                    $res = ["status" => false, "massage" => "Post not found"];
                    echo json_encode($res);
                } else{
                    while ($post = mysqli_fetch_assoc($posts)){
                        $postslist[] = $post;
                    }
                    #Отправка даных через json
                    echo json_encode($postslist);
                
                }
            break;
        }
    }
    //Вывод для плеера
    function PleerFilms($connect,$id){
        //Выводим в плеер нужный фильм по ID
        $posts = mysqli_query($connect, "SELECT `ID_film_stak`,`Name_film`, `Description`, `Link`, `Estimation_1` , `Estimation_2`, `Data` 
                                        FROM `Film_stak` WHERE '$id' = `ID_film_stak`");
                #Обработка 404
                if(mysqli_num_rows($posts) === 0){
                    http_response_code(404);
                    $res = ["status" => false, "massage" => "Post not found"];
                    echo json_encode($res);
                } else{
                    #Отправка даных через json
                    echo json_encode(mysqli_fetch_assoc($posts));
                    }
    }
    
    //Вход пользователя 
    function User_up($connect,$id){
        //Проверяем наличие регестрации пользователя
        $posts = mysqli_query($connect, "SELECT `ID_user`, `Login` FROM `User` WHERE `ID_user` = '$id'");
        #Обработка 404
        if(mysqli_num_rows($posts) === 0){
        http_response_code(404);
        $res = ["status" => false, "massage" => "Post not found"];
        echo json_encode($res);
        } else{
        #Отправка даных через json
        echo json_encode(mysqli_fetch_assoc($posts));
        }
    }

    //Записываем просмотр пользователя
    function HistorUser($connect,$data){
        $film = $data['film'];
        $user = $data['user'];
        $myDate = date("y-m-d h:i:s"); //Дата и время просмотра
        if(!$film || !$user){//Проверка на наличие ID фильма и пользователя       
        #Обработка 404
        http_response_code(404);
        $res = ["status" => false, "massage" => "Post not found"];
        echo json_encode($res);
        } else{
            //Ищем прошлые записи по этому фильму от пользывателя
            $posts = mysqli_query($connect, "SELECT `ID_film_stak`, `ID_user` FROM `History_user` WHERE `ID_film_stak` = '$film' AND `ID_user` = '$user' ");
            if (mysqli_num_rows($posts) === 0){//Не смотрел
                #Добовляем просмотр
                
                mysqli_query($connect,"INSERT INTO `History_user` (`ID_history`, `ID_film_stak`,`ID_user`,`Tymecod`, `Data`) VALUES (NULL, '$film', '$user',NULL,'$myDate')");
                //Добовляем количество просмотров
                mysqli_query($connect, "UPDATE `User` SET `Number_views` = `Number_views` + 1 WHERE `ID_user` = '$user'");
            }else if (mysqli_num_rows($posts) === 1){ //Смотрел
                #Обновляем дату последнего просмотра
                mysqli_query($connect,"UPDATE `History_user` SET `Data` =  '$myDate' WHERE `ID_film_stak` = '$film' AND `ID_user` = '$user' ");
                 //Добовляем количество просмотров
                 mysqli_query($connect, "UPDATE `User` SET `Number_views` = `Number_views` + 1 WHERE `ID_user` = '$user'");
            }else{//Баг
                #Убираем дубликаты, создаём просмотр
                mysqli_query($connect,"DELETE FROM `History_user`  WHERE `ID_film_stak` = '$film' AND `ID_user` = '$user'");
                mysqli_query($connect,"INSERT INTO `History_user` (`ID_history`, `ID_film_stak `,`ID_user`, `Data`) VALUES (NULL, '$film', '$user', '$myDate')");
                 //Добовляем количество просмотров
                 mysqli_query($connect, "UPDATE `User` SET `Number_views` = `Number_views` + 1 WHERE `ID_user` = '$user'");
            }
        }
    }

    //Отчистка истории просмотров
    function ClearHistory($connect,$data){
        $id = $data['user'];
        //Проверяем наличие регестрации пользователя
        mysqli_query($connect,"DELETE FROM History_user WHERE `ID_user` = '$id'");
    }
    function InfUser($connect, $id){
        $posts = mysqli_query($connect, "SELECT `Login`, `Email`, `Data`, `Number_views`
                                            FROM `User`WHERE `ID_user` = '$id'");
        $postslist = [];
        #Обработка 404
        if(mysqli_num_rows($posts) === 0){
            http_response_code(404);
            $res = ["status" => false, "massage" => "Post not found"];
            echo json_encode($res);
        } else{
               
                echo json_encode(mysqli_fetch_assoc($posts));
            }
    }
    ?>
   