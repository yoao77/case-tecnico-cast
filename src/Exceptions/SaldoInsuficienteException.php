<?php

declare(strict_types=1);

namespace App\Exceptions;

final class SaldoInsuficienteException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Saldo insuficiente para realizar a transferência.', 409);
    }
}
