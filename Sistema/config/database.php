<?php
declare(strict_types=1);

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '5432';
    $name = getenv('DB_NAME') ?: 'saep_db';
    $user = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: 'postgres';

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function cpfEncryptionKey(): string
{
    $configuredKey = getenv('APP_KEY');
    if ($configuredKey === false || $configuredKey === '') {
        $configuredKey = 'saep2026-development-only-change-this-key';
    }

    return hash('sha256', $configuredKey, true);
}
