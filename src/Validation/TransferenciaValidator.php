<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\DadosInvalidosException;

final class TransferenciaValidator
{
    private const array CAMPOS_OBRIGATORIOS = ['origem', 'destino', 'valorCentavos'];

    public function validar(mixed $dados): void
    {
        if (!is_array($dados)) {
            throw new DadosInvalidosException('O corpo da requisição deve ser um JSON com origem, destino e valorCentavos.');
        }

        foreach (self::CAMPOS_OBRIGATORIOS as $campo) {
            if (!array_key_exists($campo, $dados)) {
                throw new DadosInvalidosException("O campo {$campo} é obrigatório.");
            }

            if (!is_int($dados[$campo])) {
                throw new DadosInvalidosException("O campo {$campo} deve ser um número inteiro.");
            }
        }

        if ($dados['valorCentavos'] <= 0) {
            throw new DadosInvalidosException('O campo valorCentavos deve ser maior que zero.');
        }

        if ($dados['origem'] === $dados['destino']) {
            throw new DadosInvalidosException('A conta de origem deve ser diferente da conta de destino.');
        }
    }
}
