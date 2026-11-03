<?php

declare(strict_types=1);

use App\Controller\ContaController;
use App\Controller\PainelController;
use App\Controller\TransferenciaController;
use Slim\App;

return function (App $app): void {
    $app->post('/transferencias', [TransferenciaController::class, 'criar']);
    $app->get('/contas/{id:[0-9]+}', [ContaController::class, 'buscar']);
    $app->get('/contas/{id:[0-9]+}/movimentacoes', [ContaController::class, 'listarMovimentacoes']);
    $app->get('/painel', [PainelController::class, 'exibir']);
};
