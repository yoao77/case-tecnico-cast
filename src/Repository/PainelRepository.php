<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Consultas só de leitura para o painel de demonstração (GET /painel).
 */
final class PainelRepository
{
    // A transferência só ganha origem, destino e valor em transferencias_contas quando conclui. Antes disso,
    // ou se falhou, esses dados existem só no pedido guardado na fila (processamentos) ou na falha.
    private const string CONSULTA_TRANSFERENCIAS = <<<'SQL'
        SELECT t.id,
               COALESCE(tc.conta_origem_id, json_extract(pedido.dados, '$.origem')) AS origem,
               COALESCE(tc.conta_destino_id, json_extract(pedido.dados, '$.destino')) AS destino,
               COALESCE(tc.valor_centavos, json_extract(pedido.dados, '$.valorCentavos')) AS valorCentavos,
               t.status,
               t.idempotencia,
               CAST(strftime('%s', t.criado_em) AS INTEGER) AS criadoEm
          FROM transferencia t
          LEFT JOIN transferencias_contas tc ON tc.transferencia_id = t.id
          LEFT JOIN (SELECT dados FROM processamentos UNION ALL SELECT dados FROM falhas_processamento) pedido
                 ON json_extract(pedido.dados, '$.transferenciaId') = t.id
        SQL;

    private const string CAMPOS_DO_PEDIDO = <<<'SQL'
        json_extract(dados, '$.transferenciaId') AS transferenciaId,
        json_extract(dados, '$.origem') AS origem,
        json_extract(dados, '$.destino') AS destino,
        json_extract(dados, '$.valorCentavos') AS valorCentavos
        SQL;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listarContas(): array
    {
        return $this->pdo
            ->query(
                "SELECT id, saldo_centavos AS saldoCentavos, CAST(strftime('%s', atualizado_em) AS INTEGER) AS atualizadoEm
                   FROM contas
                  ORDER BY id"
            )
            ->fetchAll();
    }

    public function listarTransferencias(?int $contaId): array
    {
        if ($contaId === null) {
            return $this->pdo->query(self::CONSULTA_TRANSFERENCIAS . ' ORDER BY t.id DESC')->fetchAll();
        }

        $consulta = $this->pdo->prepare(
            'SELECT * FROM (' . self::CONSULTA_TRANSFERENCIAS . ') WHERE origem = :origem OR destino = :destino ORDER BY id DESC'
        );
        $consulta->bindValue('origem', $contaId, PDO::PARAM_INT);
        $consulta->bindValue('destino', $contaId, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function listarProcessamentos(): array
    {
        return $this->pdo
            ->query(
                'SELECT id, ' . self::CAMPOS_DO_PEDIDO . ', tentativas, reservado_em AS reservadoEm
                   FROM processamentos
                  ORDER BY id'
            )
            ->fetchAll();
    }

    public function listarFalhas(): array
    {
        return $this->pdo
            ->query(
                'SELECT id, ' . self::CAMPOS_DO_PEDIDO . ", excecao, CAST(strftime('%s', falhou_em) AS INTEGER) AS falhouEm
                   FROM falhas_processamento
                  ORDER BY id DESC"
            )
            ->fetchAll();
    }
}
