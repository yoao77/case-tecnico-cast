<?php

declare(strict_types=1);

beforeEach(function () {
    $this->app = criarApp();
});

it('retorna o ID e o saldo atual da conta', function () {
    $resposta = requisitar($this->app, 'GET', '/contas/' . CONTA_A);

    expect($resposta->getStatusCode())->toBe(200)
        ->and(corpoDa($resposta))->toBe(['id' => CONTA_A, 'saldoCentavos' => 100000]);
});

it('retorna 404 para conta inexistente', function (string $uri) {
    $resposta = requisitar($this->app, 'GET', $uri);

    expect($resposta->getStatusCode())->toBe(404)
        ->and(corpoDa($resposta))->toBe(['erro' => 'Conta 99 não encontrada.']);
})->with([
    'saldo' => ['/contas/99'],
    'movimentações' => ['/contas/99/movimentacoes'],
]);
