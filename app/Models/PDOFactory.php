<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class PDOFactory
{
    public function create(array $config): PDO
    {
        $dsn = sprintf('pgsql:host=%s;dbname=%s', $config['dbhost'], $config['dbname']);
        return new PDO($dsn, (string)$config['dbuser'], (string)$config['dbpass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
