<?php

declare(strict_types=1);

namespace App\Exceptions;

final class DadosInvalidosException extends ApiException
{
    public function __construct(string $mensagem)
    {
        parent::__construct($mensagem, 400);
    }
}
