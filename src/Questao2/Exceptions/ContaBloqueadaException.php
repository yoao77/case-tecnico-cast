<?php

declare(strict_types=1);

namespace App\Questao2\Exceptions;

use App\Exceptions\ApiException;

final class ContaBloqueadaException extends ApiException
{
    public function __construct(int $id)
    {
        parent::__construct("Conta {$id} está bloqueada.", 409);
    }
}
