<?php

declare(strict_types=1);

use App\Repository\ProcessamentoRepository;
use App\Service\TransferenciaService;

require __DIR__ . '/../vendor/autoload.php';

$container = (require __DIR__ . '/../config/bootstrap.php')()->getContainer();
$processamentoRepository = $container->get(ProcessamentoRepository::class);
$transferenciaService = $container->get(TransferenciaService::class);

foreach ($processamentoRepository->listarDisponiveis() as $processamentoId) {
    try {
        $transferenciaService->processar($processamentoId);
        echo "Processamento {$processamentoId}: concluído." . PHP_EOL;
    } catch (Throwable $erro) {
        echo "Processamento {$processamentoId}: erro - {$erro->getMessage()}" . PHP_EOL;
    }
}
