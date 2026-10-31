<?php

declare(strict_types=1);

namespace App\Service;

use App\Exceptions\ContaNaoEncontradaException;
use App\Repository\ContaRepository;
use App\Repository\TransferenciaRepository;

final class ContaService
{
    public function __construct(
        private readonly ContaRepository $contaRepository,
        private readonly TransferenciaRepository $transferenciaRepository,
    ) {
    }

    public function buscar(int $id): array
    {
        return $this->contaRepository->buscarPorId($id) ?? throw new ContaNaoEncontradaException($id);
    }

    public function listarMovimentacoes(int $id): array
    {
        $this->buscar($id);

        return $this->transferenciaRepository->listarPorConta($id);
    }
}
