<?php

declare(strict_types=1);

beforeEach(function () {
    $this->app = criarApp();
});

it('transfere da conta A para a B, atualiza os saldos e registra no histórico das duas', function () {
    $resposta = transferir($this->app, ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => 3000]);
    $transferencia = corpoDa($resposta);

    expect($resposta->getStatusCode())->toBe(201)
        ->and($transferencia['id'])->toBeInt()
        ->and(saldoDa($this->app, CONTA_A))->toBe(97000)
        ->and(saldoDa($this->app, CONTA_B))->toBe(53000);

    $movimentacao = [
        'id' => $transferencia['id'],
        'origem' => CONTA_A,
        'destino' => CONTA_B,
        'valorCentavos' => 3000,
        'status' => 'CONCLUIDA',
    ];

    expect(movimentacoesDa($this->app, CONTA_A))->toBe([$movimentacao])
        ->and(movimentacoesDa($this->app, CONTA_B))->toBe([$movimentacao]);
});

it('lista as movimentações da conta em ordem de ID, como origem e como destino', function () {
    transferir($this->app, ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => 1000]);
    transferir($this->app, ['origem' => CONTA_B, 'destino' => CONTA_A, 'valorCentavos' => 500]);

    $movimentacoes = movimentacoesDa($this->app, CONTA_A);

    expect(array_column($movimentacoes, 'id'))->toBe([1, 2])
        ->and(array_column($movimentacoes, 'origem'))->toBe([CONTA_A, CONTA_B]);
});

it('recusa com 409 quando falta saldo, sem alterar saldos nem criar movimentação', function () {
    $resposta = transferir($this->app, ['origem' => CONTA_B, 'destino' => CONTA_A, 'valorCentavos' => 50001]);

    expect($resposta->getStatusCode())->toBe(409)
        ->and(corpoDa($resposta))->toBe(['erro' => 'Saldo insuficiente para realizar a transferência.']);

    verificarQueNadaMudou($this->app);
});

it('recusa entrada inválida com 400, sem alterar saldos nem criar movimentação', function (array $corpo, string $erro) {
    $resposta = transferir($this->app, $corpo);

    expect($resposta->getStatusCode())->toBe(400)
        ->and(corpoDa($resposta))->toBe(['erro' => $erro]);

    verificarQueNadaMudou($this->app);
})->with([
    'valor zero' => [
        ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => 0],
        'O campo valorCentavos deve ser maior que zero.',
    ],
    'valor negativo' => [
        ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => -3000],
        'O campo valorCentavos deve ser maior que zero.',
    ],
    'valor fracionário' => [
        ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => 30.5],
        'O campo valorCentavos deve ser um número inteiro.',
    ],
    'valor em texto' => [
        ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => '3000'],
        'O campo valorCentavos deve ser um número inteiro.',
    ],
    'origem ausente' => [
        ['destino' => CONTA_B, 'valorCentavos' => 3000],
        'O campo origem é obrigatório.',
    ],
    'destino ausente' => [
        ['origem' => CONTA_A, 'valorCentavos' => 3000],
        'O campo destino é obrigatório.',
    ],
    'valor ausente' => [
        ['origem' => CONTA_A, 'destino' => CONTA_B],
        'O campo valorCentavos é obrigatório.',
    ],
    'corpo vazio' => [
        [],
        'O campo origem é obrigatório.',
    ],
    'origem igual ao destino' => [
        ['origem' => CONTA_A, 'destino' => CONTA_A, 'valorCentavos' => 3000],
        'A conta de origem deve ser diferente da conta de destino.',
    ],
]);

it('recusa com 404 transferência para conta inexistente, sem alterar saldos nem criar movimentação', function () {
    $resposta = transferir($this->app, ['origem' => CONTA_A, 'destino' => 99, 'valorCentavos' => 3000]);

    expect($resposta->getStatusCode())->toBe(404)
        ->and(corpoDa($resposta))->toBe(['erro' => 'Conta 99 não encontrada.']);

    verificarQueNadaMudou($this->app);
});
