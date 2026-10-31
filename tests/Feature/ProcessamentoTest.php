<?php

declare(strict_types=1);

beforeEach(function () {
    $this->app = criarApp();
    $this->pdo = $this->app->getContainer()->get(PDO::class);
});

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
