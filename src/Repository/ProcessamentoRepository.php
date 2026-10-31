<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use Throwable;

final class ProcessamentoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function enfileirar(array $dados): int
    {
        $this->pdo
            ->prepare('INSERT INTO processamentos (dados) VALUES (:dados)')
            ->execute(['dados' => json_encode($dados, JSON_THROW_ON_ERROR)]);

        return (int) $this->pdo->lastInsertId();
    }

    public function reservar(int $id): array
    {
        $this->pdo
            ->prepare('UPDATE processamentos SET reservado_em = :agora, tentativas = tentativas + 1 WHERE id = :id')
            ->execute(['id' => $id, 'agora' => time()]);

        $consulta = $this->pdo->prepare('SELECT id, dados FROM processamentos WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->fetch();
    }

    public function remover(int $id): void
    {
        $this->pdo->prepare('DELETE FROM processamentos WHERE id = :id')->execute(['id' => $id]);
    }

    public function registrarFalha(array $processamento, string $uuid, Throwable $erro): void
    {
        $this->pdo
            ->prepare('INSERT INTO falhas_processamento (uuid, dados, excecao) VALUES (:uuid, :dados, :excecao)')
            ->execute(['uuid' => $uuid, 'dados' => $processamento['dados'], 'excecao' => (string) $erro]);

        $this->remover($processamento['id']);
    }
}
