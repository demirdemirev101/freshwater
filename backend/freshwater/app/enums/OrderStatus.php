<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case SHIPPED = 'shipped';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'В очакване',
            self::SHIPPED => 'Изпратена',
            self::PROCESSING => 'Обработва се',
            self::COMPLETED => 'Завършена',
            self::CANCELLED => 'Отменена',
            self::REFUNDED => 'Върната',
        };
    }
}