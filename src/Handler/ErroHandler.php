<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exceptions\ApiException;
use App\Http\RespondeJson;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Throwable;

final class ErroHandler
{
    use RespondeJson;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, Throwable $erro): ResponseInterface
    {
        [$status, $mensagem] = match (true) {
            $erro instanceof ApiException => [$erro->getCode(), $erro->getMessage()],
            $erro instanceof HttpNotFoundException => [404, 'Rota não encontrada.'],
            $erro instanceof HttpMethodNotAllowedException => [405, 'Método não permitido para esta rota.'],
            $erro instanceof HttpException => [$erro->getCode(), $erro->getMessage()],
            default => [500, 'Erro interno no servidor.'],
        };

        if ($status === 500) {
            $this->logger->error($erro->getMessage(), ['excecao' => $erro]);
        }

        return $this->json($this->responseFactory->createResponse(), ['erro' => $mensagem], $status);
    }
}
