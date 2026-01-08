<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected $listeners = [
        'orderUpdated' => 'handleOrderRefresh',
    ];

    public function handleOrderRefresh(): void
    {
        $this->getRecord()->refresh();

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
