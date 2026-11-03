<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PainelRepository;
use App\Repository\ProcessamentoRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

/**
 * Painel de demonstração: mostra numa página o que está no banco. Só lê, então fala direto com os repositories.
 */
final class PainelController
{
    public function __construct(
        private readonly PainelRepository $painelRepository,
        private readonly ProcessamentoRepository $processamentoRepository,
        private readonly Twig $twig,
    ) {
    }

    public function exibir(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $contaSelecionada = filter_var($request->getQueryParams()['conta'] ?? null, FILTER_VALIDATE_INT) ?: null;

        return $this->twig
            ->render($response, 'painel.html.twig', [
                'contas' => $this->painelRepository->listarContas(),
                'transferencias' => $this->painelRepository->listarTransferencias($contaSelecionada),
                'processamentos' => $this->painelRepository->listarProcessamentos(),
                'retomaveisPeloWorker' => $this->processamentoRepository->listarDisponiveis(),
                'falhas' => $this->painelRepository->listarFalhas(),
                'contaSelecionada' => $contaSelecionada,
            ])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
