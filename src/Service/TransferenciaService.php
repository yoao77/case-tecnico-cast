<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\StatusTransferencia;
use App\Exceptions\SaldoInsuficienteException;
use App\Repository\ContaRepository;
use App\Repository\ProcessamentoRepository;
use App\Repository\TransferenciaRepository;
use PDO;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

final class TransferenciaService
{
    private const int MAXIMO_TENTATIVAS = 3;

    public function __construct(
        private readonly PDO $pdo,
        private readonly ContaService $contaService,
        private readonly ContaRepository $contaRepository,
        private readonly TransferenciaRepository $transferenciaRepository,
        private readonly ProcessamentoRepository $processamentoRepository,
    ) {
    }

    public function transferir(int $origem, int $destino, int $valorCentavos, ?string $idempotencia = null): array
    {
        // Idempotência (desativada): descomente este bloco, TransferenciaRepository::buscarPorIdempotencia
        // e o repasse do cabeçalho Idempotency-Key no TransferenciaController.
        // A chave repetida devolve o resultado da primeira requisição sem debitar e creditar de novo:
        // a transferência original ou, se ela falhou, o mesmo 409.
        // $original = $idempotencia !== null
        //     ? $this->transferenciaRepository->buscarPorIdempotencia($idempotencia)
        //     : null;
        //
        // if ($original !== null && $original['status'] === StatusTransferencia::Falhou->value) {
        //     throw new SaldoInsuficienteException();
        // }
        //
        // if ($original !== null) {
        //     return $original;
        // }

        $this->contaService->buscar($origem);
        $this->contaService->buscar($destino);

        $transferenciaId = $this->transferenciaRepository->criar($idempotencia);
        $processamentoId = $this->processamentoRepository->enfileirar([
            'uuid' => Uuid::uuid4()->toString(),
            'transferenciaId' => $transferenciaId,
            'origem' => $origem,
            'destino' => $destino,
            'valorCentavos' => $valorCentavos,
        ]);

        $this->processar($processamentoId);

        return $this->transferenciaRepository->buscarPorId($transferenciaId);
    }

    /**
     * Executa um processamento da fila. A requisição chama logo depois de enfileirar, e o worker
     * (bin/processar-fila.php) chama para os que ficaram para trás com a reserva vencida.
     * Débito, crédito e registro da movimentação são gravados juntos ou desfeitos juntos;
     * na falha, a transferência fica como FALHOU e o processamento vai para falhas_processamento.
     */
    public function processar(int $processamentoId): void
    {
        $processamento = $this->processamentoRepository->reservar($processamentoId);

        // Outro processo está com a reserva válida: só ele processa, para não debitar duas vezes.
        if ($processamento === null) {
            return;
        }

        $dados = json_decode($processamento['dados'], true, flags: JSON_THROW_ON_ERROR);

        if ($processamento['tentativas'] > self::MAXIMO_TENTATIVAS) {
            $this->falhar($processamento, $dados, new RuntimeException('Limite de tentativas atingido.'));
        }

        $this->transferenciaRepository->atualizarStatus($dados['transferenciaId'], StatusTransferencia::Processando);

        $this->pdo->beginTransaction();

        try {
            $this->debitarECreditar($dados);
            $this->transferenciaRepository->atualizarStatus($dados['transferenciaId'], StatusTransferencia::Concluida);
            $this->processamentoRepository->remover($processamentoId);
            $this->pdo->commit();
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->falhar($processamento, $dados, $erro);
        }
    }

    private function falhar(array $processamento, array $dados, Throwable $erro): never
    {
        $this->transferenciaRepository->atualizarStatus($dados['transferenciaId'], StatusTransferencia::Falhou);
        $this->processamentoRepository->registrarFalha($processamento, $dados['uuid'], $erro);

        throw $erro;
    }

    private function debitarECreditar(array $dados): void
    {
        // Concorrência (desativada): descomente esta linha e ContaRepository::bloquearParaAtualizacao.
        // Sem o bloqueio, duas transferências simultâneas podem ler o mesmo saldo e as duas debitarem.
        // $this->contaRepository->bloquearParaAtualizacao($dados['origem']);

        $saldoCentavos = $this->contaRepository->buscarPorId($dados['origem'])['saldoCentavos'];

        if ($saldoCentavos < $dados['valorCentavos']) {
            throw new SaldoInsuficienteException();
        }

        $this->contaRepository->debitar($dados['origem'], $dados['valorCentavos']);
        $this->contaRepository->creditar($dados['destino'], $dados['valorCentavos']);
        $this->transferenciaRepository->registrarMovimentacao(
            $dados['transferenciaId'],
            $dados['origem'],
            $dados['destino'],
            $dados['valorCentavos'],
        );
    }
}
