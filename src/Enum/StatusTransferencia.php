<?php

declare(strict_types=1);

namespace App\Enum;

enum StatusTransferencia: string
{
    case Pendente = 'PENDENTE';
    case Processando = 'PROCESSANDO';
    case Concluida = 'CONCLUIDA';
    case Falhou = 'FALHOU';
}
