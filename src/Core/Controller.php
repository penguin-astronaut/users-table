<?php

namespace PenguinAstronaut\UserTable\Core;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected \PDO $db;
    protected Environment $twig;

    public function __construct()
    {
        $this->db = App::$dbInstance;

        $loader = new FilesystemLoader(App::$appDir . '/templates');
        $this->twig = new Environment($loader);
    }

    /**
     * Render template by name
     *
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    protected function render(string $templateName, array $data = []): void
    {
        echo $this->twig->render("$templateName.twig", $data);
    }
}