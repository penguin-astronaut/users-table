<?php

namespace PenguinAstronaut\UserTable\Core;

use PDO;
use PenguinAstronaut\UserTable\Controllers\IndexController;

final class App
{
    public static PDO $dbInstance;
    public static string $appDir;

    /**
     * App run
     *
     * @return void
     */
    public function run(): void
    {
        self::$appDir = dirname($_SERVER['DOCUMENT_ROOT']);
        self::$dbInstance = DB::getInstance();

        $this->executeController();
    }

    /**
     * Execute controller by request uri
     *
     * @return void
     */
    private function executeController(): void
    {
        $controller = new IndexController();

        $methodName = trim(strtolower($_SERVER['REQUEST_URI']), '/');
        $methodName = method_exists($controller, $methodName) ? $methodName : 'index';

        $controller->$methodName();
    }
}