<?php

declare(strict_types=1);

use App\Database\BancoDeDados;

require __DIR__ . '/../vendor/autoload.php';

$app = (require __DIR__ . '/../config/bootstrap.php')();
BancoDeDados::criarEstrutura($app->getContainer()->get(PDO::class));

echo 'Banco criado: conta A (id 1) com 100000 centavos e conta B (id 2) com 50000 centavos.' . PHP_EOL;
