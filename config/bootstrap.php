<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;
use Slim\Factory\AppFactory;

return function (array $definicoes = []): App {
    $construtor = new ContainerBuilder();
    $construtor->addDefinitions(require __DIR__ . '/container.php');
    $construtor->addDefinitions($definicoes);

    $app = AppFactory::createFromContainer($construtor->build());

    (require __DIR__ . '/middleware.php')($app);
    (require __DIR__ . '/routes.php')($app);

    return $app;
};
