<?php

namespace App\enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'В брой',
            self::BANK_TRANSFER => 'Банков превод',
            self::CARD => 'Карта',
        };
    }
}
