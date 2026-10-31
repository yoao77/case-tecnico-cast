<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ContaNaoEncontradaException extends ApiException
{
    public function __construct(int $id)
    {
        parent::__construct("Conta {$id} não encontrada.", 404);
    }
}
