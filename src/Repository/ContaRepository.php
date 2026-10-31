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

    // Concorrência (desativada): usado pelo trecho comentado em TransferenciaService::debitarECreditar.
    // O SQLite não tem SELECT ... FOR UPDATE: uma escrita logo no início da transação pega o bloqueio
    // de escrita do banco, e a transferência concorrente espera esta terminar antes de ler o saldo.
    // public function bloquearParaAtualizacao(int $id): void
    // {
    //     $this->pdo
    //         ->prepare('UPDATE contas SET atualizado_em = atualizado_em WHERE id = :id')
    //         ->execute(['id' => $id]);
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
