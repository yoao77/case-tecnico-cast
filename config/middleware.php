<?php

declare(strict_types=1);

use App\Handler\ErroHandler;
use Slim\App;

return function (App $app): void {
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware(false, false, false)->setDefaultErrorHandler(ErroHandler::class);
};
