<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\StatusTransferencia;
use PDO;

final class TransferenciaRepository
{
    // LEFT JOIN porque a transferência que falhou não tem movimentação registrada.
    private const string CONSULTA_TRANSFERENCIA =
        'SELECT t.id,
                tc.conta_origem_id AS origem,
                tc.conta_destino_id AS destino,
                tc.valor_centavos AS valorCentavos,
                t.status
           FROM transferencia t
           LEFT JOIN transferencias_contas tc ON tc.transferencia_id = t.id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function criar(?string $idempotencia): int
    {
        $this->pdo
            ->prepare('INSERT INTO transferencia (status, idempotencia) VALUES (:status, :idempotencia)')
            ->execute(['status' => StatusTransferencia::Pendente->value, 'idempotencia' => $idempotencia]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizarStatus(int $id, StatusTransferencia $status): void
    {
        $this->pdo
            ->prepare('UPDATE transferencia SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status->value]);
    }

    public function registrarMovimentacao(int $transferenciaId, int $origem, int $destino, int $valorCentavos): void
    {
        $this->pdo
            ->prepare(
                'INSERT INTO transferencias_contas (transferencia_id, conta_origem_id, conta_destino_id, valor_centavos)
                 VALUES (:transferencia, :origem, :destino, :valor)'
            )
            ->execute([
                'transferencia' => $transferenciaId,
                'origem' => $origem,
                'destino' => $destino,
                'valor' => $valorCentavos,
            ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(self::CONSULTA_TRANSFERENCIA . ' WHERE t.id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->fetch() ?: null;
    }

    public function listarPorConta(int $contaId): array
    {
        $consulta = $this->pdo->prepare(
            self::CONSULTA_TRANSFERENCIA . ' WHERE tc.conta_origem_id = :origem OR tc.conta_destino_id = :destino ORDER BY t.id'
        );
        $consulta->execute(['origem' => $contaId, 'destino' => $contaId]);

        return $consulta->fetchAll();
    }

    // Idempotência (desativada): usado pelo bloco comentado em TransferenciaService::transferir.
    // public function buscarPorIdempotencia(string $idempotencia): ?array
    // {
    //     $consulta = $this->pdo->prepare(self::CONSULTA_TRANSFERENCIA . ' WHERE t.idempotencia = :idempotencia');
    //     $consulta->execute(['idempotencia' => $idempotencia]);
    //
    //     return $consulta->fetch() ?: null;
    // }
}
