<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\RespondeJson;
use App\Service\ContaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContaController
{
    use RespondeJson;

    public function __construct(private readonly ContaService $contaService)
    {
    }

    public function buscar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->json($response, $this->contaService->buscar((int) $args['id']));
    }

    public function listarMovimentacoes(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        return $this->json($response, $this->contaService->listarMovimentacoes((int) $args['id']));
    }
}
