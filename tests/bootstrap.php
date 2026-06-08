<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;

require __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

/**
 * Retourne le container de l’application pour les tests.
 */
function getTestContainer(): ContainerInterface
{
    $containerBuilder = new ContainerBuilder();

    $settings = require __DIR__ . '/../app/settings.php';
    $settings($containerBuilder);

    $dependencies = require __DIR__ . '/../app/dependencies.php';
    $dependencies($containerBuilder);

    $repositories = __DIR__ . '/../app/repositories.php';

    if (file_exists($repositories)) {
        $repositoriesConfig = require $repositories;
        $repositoriesConfig($containerBuilder);
    }

    return $containerBuilder->build();
}
