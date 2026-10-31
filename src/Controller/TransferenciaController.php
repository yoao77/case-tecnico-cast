<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\RespondeJson;
use App\Service\TransferenciaService;
use App\Validation\TransferenciaValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TransferenciaController
{
    use RespondeJson;

    public function __construct(
        private readonly TransferenciaService $transferenciaService,
        private readonly TransferenciaValidator $transferenciaValidator,
    ) {
    }

    public function criar(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dados = $request->getParsedBody();
        $this->transferenciaValidator->validar($dados);

        $transferencia = $this->transferenciaService->transferir(
            $dados['origem'],
            $dados['destino'],
            $dados['valorCentavos'],
        );

        return $this->json($response, $transferencia, 201);
    }
}
