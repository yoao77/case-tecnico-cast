<?php

declare(strict_types=1);

namespace App\Questao2\Entity;

use App\Questao2\Exceptions\ContaBloqueadaException;

final class Conta
{
    public function __construct(
        public readonly int $id,
        public private(set) int $saldoCentavos,
        public readonly bool $bloqueada = false,
    ) {
    }

    public function debitar(int $valorCentavos): void
    {
        $this->saldoCentavos -= $valorCentavos;
    }

    public function creditar(int $valorCentavos): void
    {
        if ($this->bloqueada) {
            throw new ContaBloqueadaException($this->id);
        }

        $this->saldoCentavos += $valorCentavos;
    }
}
