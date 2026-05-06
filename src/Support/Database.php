<?php

declare(strict_types=1);

namespace IotBoardLab\Support;

use PDO;
use PDOException;

final class Database
{
    public static function connection(): ?PDO
    {
        $host = Env::get('DB_HOST');
        $database = Env::get('DB_DATABASE');
        $username = Env::get('DB_USERNAME');
        $password = Env::get('DB_PASSWORD');

        if (!$host || !$database || !$username) {
            return null;
        }

        $port = Env::get('DB_PORT', '3306');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            return new PDO($dsn, $username, $password ?: '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException) {
            return null;
        }
    }
}

