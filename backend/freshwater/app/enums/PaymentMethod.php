<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case BANK_TRANSFER = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Наложен платеж',
            self::BANK_TRANSFER => 'Банков превод',
        };
    }
}
