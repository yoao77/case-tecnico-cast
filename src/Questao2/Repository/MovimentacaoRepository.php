<?php

declare(strict_types=1);

namespace App\Questao2\Repository;

final class MovimentacaoRepository
{
    private array $movimentacoes = [];

    public function registrar(int $origem, int $destino, int $valorCentavos): void
    {
        $this->movimentacoes[] = [
            'origem' => $origem,
            'destino' => $destino,
            'valorCentavos' => $valorCentavos,
        ];
    }

    public function listar(): array
    {
        return $this->movimentacoes;
    }
}
