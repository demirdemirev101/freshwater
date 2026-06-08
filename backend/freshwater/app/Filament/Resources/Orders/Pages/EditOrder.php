<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderReadyForShipment;
use App\Filament\Resources\Orders\OrderResource;
use App\Policies\CancelOrderPolicy;
use App\Policies\ConfirmBankTransferPolicy;
use App\Policies\IsOrderLockedPolicy;
use App\Policies\ShipmentPollingPolicy;
use App\Services\OrderCancellationService;
use App\Services\OrderReturnRequestService;
use App\Services\Shipment\ShipmentTrackingSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.orders.pages.edit-order';

    public function handleOrderRefresh(): void
    {
        $this->getRecord()->refresh();
        $this->fillForm();
    }

    private function refreshUi(): void
    {
        $this->handleOrderRefresh();
        $this->dispatch('$refresh');
        $this->dispatch('orderUpdated');
    }

    public function shouldPollShipmentStatus(): bool
    {
        $record = $this->getRecord()->fresh(['shipment', 'returnShipment']);

        if (! $record) {
            return false;
        }

        return app(ShipmentPollingPolicy::class)->shouldPollShipmentStatus($record);
    }

    public function pollShipmentStatus(): void
    {
        if (property_exists($this, 'mountedActions') && ! empty($this->mountedActions)) {
            return;
        }

        $record = $this->getRecord()->loadMissing(['shipment', 'returnShipment']);

        if (! $record) {
            return;
        }

        $freshRecord = $record->fresh(['shipment', 'returnShipment']);

        if ($freshRecord && $this->hasShipmentUiChanges($record, $freshRecord)) {
            $this->refreshUi();

            return;
        }

        $changed = app(ShipmentTrackingSyncService::class)->syncShipmentTracking($freshRecord ?? $record);

        if ($changed) {
            $this->refreshUi();
        }
    }

    private function hasShipmentUiChanges($currentRecord, $freshRecord): bool
    {
        if ($currentRecord->status !== $freshRecord->status) {
            return true;
        }

        return $this->shipmentSnapshot($currentRecord->shipment) !== $this->shipmentSnapshot($freshRecord->shipment)
            || $this->shipmentSnapshot($currentRecord->returnShipment) !== $this->shipmentSnapshot($freshRecord->returnShipment);
    }

    private function shipmentSnapshot($shipment): ?array
    {
        if (! $shipment) {
            return null;
        }

        return [
            'id' => $shipment->id,
            'status' => $shipment->status,
            'tracking_number' => $shipment->tracking_number,
            'carrier_shipment_id' => $shipment->carrier_shipment_id,
            'label_url' => $shipment->label_url,
            'sent_to_carrier_at' => optional($shipment->sent_to_carrier_at)?->toDateTimeString(),
            'updated_at' => optional($shipment->updated_at)?->toDateTimeString(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm_cod')
                ->label('Потвърди поръчка (наложен платеж)')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => $this->record->payment_method === 'cod'
                    && $this->record->status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Потвърждаване на поръчка')
                ->modalDescription('Сигурни ли сте, че поръчката е потвърдена? Пратката ще се изпрати към Еконт.')
                ->action(function () {
                    $this->record->updateQuietly([
                        'status' => OrderStatus::READY_FOR_SHIPMENT->value,
                    ]);

                    event(new OrderReadyForShipment($this->record->id));

                    Notification::make()
                        ->success()
                        ->title('Поръчката е потвърдена')
                        ->body('Пратката ще бъде изпратена към Еконт.')
                        ->send();

                    $this->refreshUi();
                }),

            Action::make('confirm_bank_transfer')
                ->label('Потвърди банков превод')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => app(ConfirmBankTransferPolicy::class)->canConfirmBankTransfer($this->record))
                ->requiresConfirmation()
                ->modalHeading('Потвърждаване на плащане')
                ->modalDescription('Сигурни ли сте, че плащането е постъпило? Пратката ще се изпрати към Еконт.')
                ->action(function () {
                    $this->record->updateQuietly([
                        'payment_status' => PaymentStatus::PAID->value,
                        'status' => OrderStatus::READY_FOR_SHIPMENT->value,
                    ]);

                    event(new OrderReadyForShipment($this->record->id));

                    Notification::make()
                        ->success()
                        ->title('Плащането е потвърдено')
                        ->body('Пратката ще бъде изпратена към Еконт.')
                        ->send();

                    $this->refreshUi();
                }),

            Action::make('cancel_order')
                ->label('Откажи поръчка')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => app(CancelOrderPolicy::class)->canCancelOrder($this->record))
                ->requiresConfirmation()
                ->modalHeading('Отказ на поръчка')
                ->modalDescription('Сигурни ли сте, че искате да откажете поръчката?')
                ->action(function () {
                    try {
                        app(OrderCancellationService::class)->cancel($this->record);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Неуспешно отказване на поръчката')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Поръчката е отказана')
                        ->body('Поръчката беше анулирана и при Stripe плащане беше опитано възстановяване.')
                        ->send();

                    $this->refreshUi();
                }),

            Action::make('request_return')
                ->label('Заяви връщане')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => app(CancelOrderPolicy::class)->canRequestReturn($this->record))
                ->requiresConfirmation()
                ->modalHeading('Заявка за връщане')
                ->modalDescription('Сигурни ли сте, че искате да заявите връщане на поръчката?')
                ->action(function () {
                    try {
                        app(OrderReturnRequestService::class)->requestReturn($this->record);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Неуспешна заявка за връщане')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Заявено е връщане')
                        ->body('Създадена е обратна пратка, а при Stripe плащане беше опитано възстановяване.')
                        ->send();

                    $this->refreshUi();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->record && app(IsOrderLockedPolicy::class)->isLocked($this->record)) {
            return [];
        }

        return parent::getFormActions();
    }
}
