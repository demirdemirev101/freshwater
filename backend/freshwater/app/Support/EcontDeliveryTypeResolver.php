<?php

namespace App\Support;

final class EcontDeliveryTypeResolver
{
    public static function resolve(?string $shippingMethod, ?string $officeCode): string
    {
        $normalizedMethod = is_string($shippingMethod)
            ? strtolower(trim($shippingMethod))
            : null;
        $normalizedOfficeCode = is_string($officeCode)
            ? strtoupper(trim($officeCode))
            : null;

        if ($normalizedMethod === 'address') {
            return 'address';
        }

        if ($normalizedMethod === 'apm') {
            return 'apm';
        }

        if ($normalizedMethod === 'office') {
            return str_starts_with($normalizedOfficeCode ?? '', 'APM')
                ? 'apm'
                : 'office';
        }

        if (! empty($normalizedOfficeCode)) {
            return str_starts_with($normalizedOfficeCode, 'APM')
                ? 'apm'
                : 'office';
        }

        return 'address';
    }
}
