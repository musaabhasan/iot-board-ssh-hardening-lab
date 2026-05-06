<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'IotBoardLab\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';

    if (is_file($path)) {
        require $path;
    }
});

use IotBoardLab\Support\Env;

$root = dirname(__DIR__);
Env::load($root . DIRECTORY_SEPARATOR . '.env');

if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(Env::get('SESSION_NAME', 'iot_board_lab_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

return [
    'root' => $root,
    'catalog' => require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'paper.php',
];

