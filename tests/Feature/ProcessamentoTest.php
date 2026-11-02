<?php

declare(strict_types=1);

use App\Enum\StatusTransferencia;
use App\Repository\ProcessamentoRepository;
use App\Repository\TransferenciaRepository;
use App\Service\TransferenciaService;
use Slim\App;

beforeEach(function () {
    $this->app = criarApp();
    $this->pdo = $this->app->getContainer()->get(PDO::class);
    $this->processamentoRepository = $this->app->getContainer()->get(ProcessamentoRepository::class);
    $this->transferenciaService = $this->app->getContainer()->get(TransferenciaService::class);
});

/**
 * Deixa a fila como fica quando o processo cai no meio: a transferência de 3000 da conta A para a B
 * em PROCESSANDO e o processamento reservado, sem ninguém para terminar.
 */
function criarProcessamentoParado(App $app, int $tentativas, int $reservadoHaSegundos): int
{
    $container = $app->getContainer();
    $transferenciaRepository = $container->get(TransferenciaRepository::class);

    $transferenciaId = $transferenciaRepository->criar(null);
    $transferenciaRepository->atualizarStatus($transferenciaId, StatusTransferencia::Processando);

    $processamentoId = $container->get(ProcessamentoRepository::class)->enfileirar([
        'uuid' => 'processamento-parado',
        'transferenciaId' => $transferenciaId,
        'origem' => CONTA_A,
        'destino' => CONTA_B,
        'valorCentavos' => 3000,
    ]);

    $container->get(PDO::class)
        ->prepare('UPDATE processamentos SET tentativas = :tentativas, reservado_em = :reservadoEm WHERE id = :id')
        ->execute(['tentativas' => $tentativas, 'reservadoEm' => time() - $reservadoHaSegundos, 'id' => $processamentoId]);

    return $processamentoId;
}

it('conclui a transferência e esvazia a fila de processamento', function () {
    transferir($this->app, ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => 3000]);

    expect($this->pdo->query('SELECT status FROM transferencia')->fetchAll(PDO::FETCH_COLUMN))->toBe(['CONCLUIDA'])
        ->and($this->pdo->query('SELECT COUNT(*) FROM processamentos')->fetchColumn())->toBe(0)
        ->and($this->pdo->query('SELECT COUNT(*) FROM falhas_processamento')->fetchColumn())->toBe(0);
});

it('marca a transferência como FALHOU e guarda a falha do processamento quando falta saldo', function () {
    transferir($this->app, ['origem' => CONTA_B, 'destino' => CONTA_A, 'valorCentavos' => 50001]);

    $falhas = $this->pdo->query('SELECT dados, excecao FROM falhas_processamento')->fetchAll();

    expect($this->pdo->query('SELECT status FROM transferencia')->fetchAll(PDO::FETCH_COLUMN))->toBe(['FALHOU'])
        ->and($this->pdo->query('SELECT COUNT(*) FROM processamentos')->fetchColumn())->toBe(0)
        ->and($falhas)->toHaveCount(1)
        ->and(json_decode($falhas[0]['dados'], true))->toMatchArray([
            'origem' => CONTA_B,
            'destino' => CONTA_A,
            'valorCentavos' => 50001,
        ])
        ->and($falhas[0]['excecao'])->toContain('SaldoInsuficienteException');
});

it('retoma o processamento parado com a reserva vencida e conclui a transferência uma vez só', function () {
    $processamentoId = criarProcessamentoParado($this->app, tentativas: 1, reservadoHaSegundos: 60);

    expect($this->processamentoRepository->listarDisponiveis())->toBe([$processamentoId]);

    $this->transferenciaService->processar($processamentoId);

    expect($this->pdo->query('SELECT status FROM transferencia')->fetchAll(PDO::FETCH_COLUMN))->toBe(['CONCLUIDA'])
        ->and($this->pdo->query('SELECT COUNT(*) FROM processamentos')->fetchColumn())->toBe(0)
        ->and(saldoDa($this->app, CONTA_A))->toBe(97000)
        ->and(saldoDa($this->app, CONTA_B))->toBe(53000)
        ->and(movimentacoesDa($this->app, CONTA_A))->toHaveCount(1)
        ->and($this->processamentoRepository->listarDisponiveis())->toBe([]);
});

it('não retoma o processamento enquanto a reserva ainda vale', function () {
    $processamentoId = criarProcessamentoParado($this->app, tentativas: 1, reservadoHaSegundos: 5);

    $this->transferenciaService->processar($processamentoId);

    expect($this->processamentoRepository->listarDisponiveis())->toBe([])
        ->and($this->pdo->query('SELECT tentativas FROM processamentos')->fetchColumn())->toBe(1)
        ->and($this->pdo->query('SELECT status FROM transferencia')->fetchAll(PDO::FETCH_COLUMN))->toBe(['PROCESSANDO']);

    verificarQueNadaMudou($this->app);
});

it('desiste depois de 3 tentativas, marca FALHOU e guarda a falha sem alterar saldos', function () {
    $processamentoId = criarProcessamentoParado($this->app, tentativas: 3, reservadoHaSegundos: 60);

    expect(fn () => $this->transferenciaService->processar($processamentoId))
        ->toThrow(RuntimeException::class, 'Limite de tentativas atingido.');

    expect($this->pdo->query('SELECT status FROM transferencia')->fetchAll(PDO::FETCH_COLUMN))->toBe(['FALHOU'])
        ->and($this->pdo->query('SELECT COUNT(*) FROM processamentos')->fetchColumn())->toBe(0)
        ->and($this->pdo->query('SELECT excecao FROM falhas_processamento')->fetchColumn())
        ->toContain('Limite de tentativas atingido.');

    verificarQueNadaMudou($this->app);
});
