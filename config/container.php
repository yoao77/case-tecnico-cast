<?php

declare(strict_types=1);

use App\Database\BancoDeDados;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;

use function DI\autowire;

return [
    PDO::class => fn (): PDO => BancoDeDados::conectar(__DIR__ . '/../var/banco/transferencias.sqlite'),

    LoggerInterface::class => fn (): LoggerInterface => new Logger('requisicoes', [
        new StreamHandler(__DIR__ . '/../var/logs/requisicoes.log'),
    ]),

    ResponseFactoryInterface::class => autowire(ResponseFactory::class),
];
