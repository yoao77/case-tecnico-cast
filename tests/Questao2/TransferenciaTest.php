<?php

declare(strict_types=1);

use App\Exceptions\SaldoInsuficienteException;
use App\Questao2\Entity\Conta;
use App\Questao2\Exceptions\ContaBloqueadaException;
use App\Questao2\Repository\MovimentacaoRepository;
use App\Questao2\Service\TransferenciaOriginalService;
use App\Questao2\Service\TransferenciaService;

beforeEach(function () {
    $this->movimentacaoRepository = new MovimentacaoRepository();
    $this->contaA = new Conta(CONTA_A, 10000);
});

it('reproduz a falha original: com B bloqueada, A é debitada e B não recebe', function () {
    $contaB = new Conta(CONTA_B, 5000, bloqueada: true);
    $servico = new TransferenciaOriginalService($this->movimentacaoRepository);

    expect(fn () => $servico->transferir($this->contaA, $contaB, 3000))
        ->toThrow(ContaBloqueadaException::class, 'Conta 2 está bloqueada.');

    expect($this->contaA->saldoCentavos)->toBe(7000)
        ->and($contaB->saldoCentavos)->toBe(5000)
        ->and($this->movimentacaoRepository->listar())->toBe([]);
});

it('com a correção, B bloqueada não altera saldos nem cria movimentação', function () {
    $contaB = new Conta(CONTA_B, 5000, bloqueada: true);
    $servico = new TransferenciaService($this->movimentacaoRepository);

    expect(fn () => $servico->transferir($this->contaA, $contaB, 3000))
        ->toThrow(ContaBloqueadaException::class, 'Conta 2 está bloqueada.');

    expect($this->contaA->saldoCentavos)->toBe(10000)
        ->and($contaB->saldoCentavos)->toBe(5000)
        ->and($this->movimentacaoRepository->listar())->toBe([]);
});

it('transfere com B desbloqueada, atualiza os saldos e registra a movimentação', function () {
    $contaB = new Conta(CONTA_B, 5000);
    $servico = new TransferenciaService($this->movimentacaoRepository);

    $servico->transferir($this->contaA, $contaB, 3000);

    expect($this->contaA->saldoCentavos)->toBe(7000)
        ->and($contaB->saldoCentavos)->toBe(8000)
        ->and($this->movimentacaoRepository->listar())->toBe([
            ['origem' => CONTA_A, 'destino' => CONTA_B, 'valorCentavos' => 3000],
        ]);
});

it('recusa saldo insuficiente sem alterar saldos nem criar movimentação', function () {
    $contaB = new Conta(CONTA_B, 5000);
    $servico = new TransferenciaService($this->movimentacaoRepository);

    expect(fn () => $servico->transferir($this->contaA, $contaB, 10001))
        ->toThrow(SaldoInsuficienteException::class);

    expect($this->contaA->saldoCentavos)->toBe(10000)
        ->and($contaB->saldoCentavos)->toBe(5000)
        ->and($this->movimentacaoRepository->listar())->toBe([]);
});
