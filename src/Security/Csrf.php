<?php

declare(strict_types=1);

namespace IotBoardLab\Security;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return 'cli-token';
        }

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        return is_string($token)
            && isset($_SESSION['_csrf'])
            && hash_equals((string) $_SESSION['_csrf'], $token);
    }
}

