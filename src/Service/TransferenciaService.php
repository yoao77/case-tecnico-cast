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
use Throwable;

final class TransferenciaService
{
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
     * Executa o processamento enfileirado dentro da própria requisição, como um worker faria.
     * Débito, crédito e registro da movimentação são gravados juntos ou desfeitos juntos;
     * na falha, a transferência fica como FALHOU e o processamento vai para falhas_processamento.
     */
    private function processar(int $processamentoId): void
    {
        $processamento = $this->processamentoRepository->reservar($processamentoId);
        $dados = json_decode($processamento['dados'], true, flags: JSON_THROW_ON_ERROR);

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

            $this->transferenciaRepository->atualizarStatus($dados['transferenciaId'], StatusTransferencia::Falhou);
            $this->processamentoRepository->registrarFalha($processamento, $dados['uuid'], $erro);

            throw $erro;
        }
    }

    private function debitarECreditar(array $dados): void
    {
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
