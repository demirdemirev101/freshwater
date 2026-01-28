<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Events\OrderReadyForShipment;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
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
            Action::make('confirm_bank_transfer')
                ->label('Потвърди банков превод')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => $this->record->payment_method === 'bank_transfer'
                    && $this->record->payment_status !== 'paid')
                ->requiresConfirmation()
                ->modalHeading('Потвърждаване на плащане')
                ->modalDescription('Сигурни ли сте, че плащането е постъпило? Пратката ще се изпрати към Еконт.')
                ->action(function () {
                    $this->record->updateQuietly([
                        'payment_status' => 'paid',
                        'status' => 'ready_for_shipment',
                    ]);

                    event(new OrderReadyForShipment($this->record->id));

                    Notification::make()
                        ->success()
                        ->title('Плащането е потвърдено')
                        ->body('Пратката ще бъде изпратена към Еконт.')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
