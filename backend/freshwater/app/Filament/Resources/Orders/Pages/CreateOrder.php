<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\OrderService;

class CreateOrder extends CreateRecord
{
   protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        $orderService = app(OrderService::class);
        $orderService->recalculateTotal($this->record);
    }
}
