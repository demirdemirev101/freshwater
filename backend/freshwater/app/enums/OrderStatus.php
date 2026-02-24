<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case PENDING_REVIEW = 'pending_review';
    case PROCESSING = 'processing';
    case READY_FOR_SHIPMENT = 'ready_for_shipment';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RETURN_REQUESTED = 'return_requested';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'В очакване',
            self::PENDING_REVIEW => 'Чака потвърждение',
            self::PROCESSING => 'Обработва се',
            self::READY_FOR_SHIPMENT => 'Готова за изпращане',
            self::SHIPPED => 'Изпратена',
            self::COMPLETED => 'Завършена',
            self::IN_TRANSIT => 'В транзит',
            self::CANCELLED => 'Отменена',
            self::RETURN_REQUESTED => 'Заявено връщане',
            self::RETURNED => 'Върната',
        };
    }
}
