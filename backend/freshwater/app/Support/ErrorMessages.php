<?php

namespace App\Support;

class ErrorMessages
{
    public const SHIPMENT_CREATE_FAILED = 'Неуспешно създаване на пратка в Еконт.';
    public const SHIPMENT_CREATE_FAILED_AFTER_RETRIES = 'Пратката не можа да бъде създадена след няколко опита.';
    public const BANK_TRANSFER_SHIPPING_FAILED = 'Неуспешно изчисляване на доставка по банков превод.';
}
