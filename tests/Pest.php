<?php

declare(strict_types=1);

use App\Database\BancoDeDados;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

const CONTA_A = 1;
const CONTA_B = 2;

function criarApp(): App
{
    $pdo = BancoDeDados::conectar(':memory:');
    BancoDeDados::criarEstrutura($pdo);

    return (require __DIR__ . '/../config/bootstrap.php')([
        PDO::class => $pdo,
        LoggerInterface::class => new NullLogger(),
    ]);
}

function requisitar(App $app, string $metodo, string $uri, ?array $corpo = null): ResponseInterface
{
    $requisicao = (new ServerRequestFactory())->createServerRequest($metodo, $uri);

    if ($corpo !== null) {
        $requisicao = $requisicao
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new StreamFactory())->createStream(json_encode($corpo)));
    }

    return $app->handle($requisicao);
}

function transferir(App $app, array $corpo): ResponseInterface
{
    return requisitar($app, 'POST', '/transferencias', $corpo);
}

function corpoDa(ResponseInterface $resposta): mixed
{
    return json_decode((string) $resposta->getBody(), true);
}

function saldoDa(App $app, int $conta): int
{
    return corpoDa(requisitar($app, 'GET', "/contas/{$conta}"))['saldoCentavos'];
}

function movimentacoesDa(App $app, int $conta): array
{
    return corpoDa(requisitar($app, 'GET', "/contas/{$conta}/movimentacoes"));
}

function verificarQueNadaMudou(App $app): void
{
    expect(saldoDa($app, CONTA_A))->toBe(100000)
        ->and(saldoDa($app, CONTA_B))->toBe(50000)
        ->and(movimentacoesDa($app, CONTA_A))->toBe([])
        ->and(movimentacoesDa($app, CONTA_B))->toBe([]);
}
