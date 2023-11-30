<?php

use PenguinAstronaut\UserTable\Core\App;

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    $app = new App();
    $app->run();
} catch (Exception $exception) {
    if ($_ENV['MODE'] === 'development') {
        echo $exception->getMessage();
    } else {
        exit('В работе приложения произошла ошибка');
    }
}
