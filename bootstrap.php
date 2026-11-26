<?php

declare(strict_types=1);

define('ROOTDIR', __DIR__ . DIRECTORY_SEPARATOR);

require_once ROOTDIR . 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOTDIR);
$dotenv->safeLoad();

try {
    $PDO = (new App\Models\PDOFactory())->create([
        'dbhost' => $_ENV['DB_HOST'] ?? 'localhost',
        'dbname' => $_ENV['DB_NAME'] ?? 'ct275_vivutours',
        'dbuser' => $_ENV['DB_USER'] ?? 'postgres',
        'dbpass' => $_ENV['DB_PASS'] ?? '',
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
        exit('Không thể kết nối cơ sở dữ liệu.');
    }
    exit('Không thể kết nối PostgreSQL: ' . e($exception->getMessage()));
}

$AUTHGUARD = new App\SessionGuard($PDO);
