<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ContaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT id, saldo_centavos AS saldoCentavos FROM contas WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->fetch() ?: null;
    }

    public function debitar(int $id, int $valorCentavos): void
    {
        $this->somarAoSaldo($id, -$valorCentavos);
    }

    public function creditar(int $id, int $valorCentavos): void
    {
        $this->somarAoSaldo($id, $valorCentavos);
    }

    // Concorrência (conceitual, não roda no SQLite, que não tem FOR UPDATE): usado pela leitura comentada em
    // TransferenciaService::debitarECreditar. Dentro de uma transação, o FOR UPDATE trava a linha da conta até o
    // COMMIT ou ROLLBACK: outra transferência da mesma conta fica parada nesta consulta e, quando passa, lê o
    // saldo já atualizado.
    // public function buscarPorIdParaAtualizacao(int $id): ?array
    // {
    //     $consulta = $this->pdo->prepare(
    //         'SELECT id, saldo_centavos AS saldoCentavos FROM contas WHERE id = :id FOR UPDATE'
    //     );
    //     $consulta->execute(['id' => $id]);
    //
    //     return $consulta->fetch() ?: null;
    // }

    private function somarAoSaldo(int $id, int $valorCentavos): void
    {
        $this->pdo
            ->prepare(
                'UPDATE contas
                    SET saldo_centavos = saldo_centavos + :valor,
                        atualizado_em = CURRENT_TIMESTAMP
                  WHERE id = :id'
            )
            ->execute(['id' => $id, 'valor' => $valorCentavos]);
    }
}
