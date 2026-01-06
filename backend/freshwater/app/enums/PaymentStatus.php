<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Неплатена',
            self::PAID => 'Платена',
            self::FAILED => 'Неуспешно плащане',
            self::REFUNDED => 'Върнати средства',
        };
    }
}