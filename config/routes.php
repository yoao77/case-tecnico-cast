<?php

declare(strict_types=1);

use App\Controller\ContaController;
use Slim\App;

return function (App $app): void {
    $app->get('/contas/{id:[0-9]+}', [ContaController::class, 'buscar']);
    $app->get('/contas/{id:[0-9]+}/movimentacoes', [ContaController::class, 'listarMovimentacoes']);
};
