<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Очаква се',
            self::UNPAID => 'Неплатено',
            self::PAID => 'Платено',
            self::FAILED => 'Неуспешно плащане',
            self::REFUNDED => 'Върнати средства',
        };
    }
}
