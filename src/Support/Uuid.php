<?php

declare(strict_types=1);

namespace IotBoardLab\Support;

use RuntimeException;

final class Uuid
{
    public static function v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        $hex = bin2hex($data);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));

        if (!is_string($uuid)) {
            throw new RuntimeException('Unable to create UUID.');
        }

        return $uuid;
    }
}

