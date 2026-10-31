<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class BancoDeDados
{
    public static function conectar(string $caminho): PDO
    {
        $pdo = new PDO("sqlite:{$caminho}", options: [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    public static function criarEstrutura(PDO $pdo): void
    {
        $pdo->exec(file_get_contents(__DIR__ . '/../../database/schema.sql'));
        $pdo->exec(file_get_contents(__DIR__ . '/../../database/seed.sql'));
    }
}
