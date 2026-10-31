<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface;

trait RespondeJson
{
    private function json(ResponseInterface $response, mixed $dados, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
