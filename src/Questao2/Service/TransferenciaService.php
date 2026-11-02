<?php

declare(strict_types=1);

namespace App\Questao2\Service;

use App\Exceptions\DadosInvalidosException;
use App\Exceptions\SaldoInsuficienteException;
use App\Questao2\Entity\Conta;
use App\Questao2\Exceptions\ContaBloqueadaException;
use App\Questao2\Repository\MovimentacaoRepository;

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

        // Correção: sem transação, nada desfaz o débito. Por isso tudo o que pode falhar é conferido
        // antes da primeira alteração; antes, o bloqueio só era visto no creditar, com a origem já debitada.
        if ($destino->bloqueada) {
            throw new ContaBloqueadaException($destino->id);
        }

        $origem->debitar($valorCentavos);
        $destino->creditar($valorCentavos);
        $this->movimentacaoRepository->registrar($origem->id, $destino->id, $valorCentavos);
    }
}
