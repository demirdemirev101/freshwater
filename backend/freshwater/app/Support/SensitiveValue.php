<?php

namespace App\Support;

final class SensitiveValue
{
    public static function fingerprint(?string $value): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        return substr(hash('sha256', $normalized), 0, 16);
    }
}
