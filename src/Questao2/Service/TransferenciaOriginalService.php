<?php

declare(strict_types=1);

namespace App\Questao2\Service;

use App\Exceptions\DadosInvalidosException;
use App\Exceptions\SaldoInsuficienteException;
use App\Questao2\Entity\Conta;
use App\Questao2\Repository\MovimentacaoRepository;

/**
 * O pseudocódigo da questão 2 traduzido como veio, com a falha: a origem é debitada antes de o
 * bloqueio do destino ser verificado. Fica no projeto só para os testes reproduzirem o problema;
 * a versão corrigida é o TransferenciaService.
 */
final class TransferenciaOriginalService
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

        $origem->debitar($valorCentavos);
        $destino->creditar($valorCentavos);
        $this->movimentacaoRepository->registrar($origem->id, $destino->id, $valorCentavos);
    }
}
