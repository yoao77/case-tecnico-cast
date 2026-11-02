<?php

declare(strict_types=1);

namespace App\Questao2\Service;

use App\Exceptions\DadosInvalidosException;
use App\Exceptions\SaldoInsuficienteException;
use App\Questao2\Entity\Conta;
use App\Questao2\Exceptions\ContaBloqueadaException;
use App\Questao2\Repository\MovimentacaoRepository;
use Throwable;

final class TransferenciaService
{
    public function __construct(private readonly MovimentacaoRepository $movimentacaoRepository)
    {
    }

    public function transferir(Conta $origem, Conta $destino, int $valorCentavos): void
    {
        if ($valorCentavos <= 0) {
            throw new DadosInvalidosException('Valor inválido.');
        }

        if ($origem->saldoCentavos < $valorCentavos) {
            throw new SaldoInsuficienteException();
        }

        // Correção, parte 1: o que já se sabe que pode falhar é conferido antes da primeira alteração.
        // Antes, o bloqueio só era visto no creditar, com a origem já debitada.
        if ($destino->bloqueada) {
            throw new ContaBloqueadaException($destino->id);
        }

        $saldoOrigem = $origem->saldoCentavos;
        $saldoDestino = $destino->saldoCentavos;

        // Correção, parte 2: transação em memória. Se qualquer passo falhar depois do débito, inclusive
        // por algo que a validação não previu, os saldos voltam ao que eram e o erro segue para quem chamou.
        try {
            $origem->debitar($valorCentavos);
            $destino->creditar($valorCentavos);
            $this->movimentacaoRepository->registrar($origem->id, $destino->id, $valorCentavos);
        } catch (Throwable $erro) {
            $origem->restaurarSaldo($saldoOrigem);
            $destino->restaurarSaldo($saldoDestino);

            throw $erro;
        }
    }
}
