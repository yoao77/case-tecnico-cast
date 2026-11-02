<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use Throwable;

final class ProcessamentoRepository
{
    // Como o retry_after do Laravel: uma reserva mais velha que isso é tratada como abandonada
    // (o processo caiu no meio) e o processamento pode ser reservado de novo pelo worker.
    private const int SEGUNDOS_PARA_RESERVA_VENCER = 30;

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

    /**
     * Processamentos que ficaram para trás: reservados há mais tempo que o limite, ou que nem chegaram
     * a ser reservados e entraram na fila antes disso. Os recentes ainda estão com a requisição que os criou.
     */
    public function listarDisponiveis(): array
    {
        $limite = time() - self::SEGUNDOS_PARA_RESERVA_VENCER;

        $consulta = $this->pdo->prepare(
            'SELECT id
               FROM processamentos
              WHERE disponivel_em <= CURRENT_TIMESTAMP
                AND (reservado_em <= :limiteReserva OR (reservado_em IS NULL AND criado_em <= :limiteCriacao))
              ORDER BY id'
        );
        $consulta->execute(['limiteReserva' => $limite, 'limiteCriacao' => gmdate('Y-m-d H:i:s', $limite)]);

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Reserva só se ninguém estiver com o processamento: livre ou com a reserva vencida. O UPDATE com a
     * condição é atômico, então entre o worker e uma requisição (ou dois workers) só um consegue.
     */
    public function reservar(int $id): ?array
    {
        $agora = time();

        $reserva = $this->pdo->prepare(
            'UPDATE processamentos
                SET reservado_em = :agora, tentativas = tentativas + 1
              WHERE id = :id AND (reservado_em IS NULL OR reservado_em <= :limite)'
        );
        $reserva->execute(['id' => $id, 'agora' => $agora, 'limite' => $agora - self::SEGUNDOS_PARA_RESERVA_VENCER]);

        if ($reserva->rowCount() === 0) {
            return null;
        }

        $consulta = $this->pdo->prepare('SELECT id, dados, tentativas FROM processamentos WHERE id = :id');
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
