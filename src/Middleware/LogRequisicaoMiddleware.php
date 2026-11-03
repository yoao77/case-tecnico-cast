<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class LogRequisicaoMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // O painel de demonstração fica fora do log, para não misturar as visualizações com as chamadas da API.
        if ($request->getUri()->getPath() === '/painel') {
            return $handler->handle($request);
        }

        $response = $handler->handle($request);

        $this->logger->info("{$request->getMethod()} {$request->getUri()->getPath()}", [
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            'requisicao' => $this->decodificar((string) $request->getBody()),
            'status' => $response->getStatusCode(),
            'resposta' => $this->decodificar((string) $response->getBody()),
        ]);

        return $response;
    }

    private function decodificar(string $corpo): mixed
    {
        return json_decode($corpo, true) ?? $corpo;
    }
}
