<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case READY_FOR_SHIPMENT = 'ready_for_shipment';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RETURNED  = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'В очакване',
            self::PROCESSING => 'Обработва се',
            self::READY_FOR_SHIPMENT => 'Готова за изпращане',
            self::SHIPPED => 'Изпратена',
            self::COMPLETED => 'Завършена',
            self::CANCELLED => 'Отменена',
            self::RETURNED => 'Върната',
        };
    }
}