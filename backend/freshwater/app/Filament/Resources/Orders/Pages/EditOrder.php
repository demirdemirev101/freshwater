<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderReadyForShipment;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderCancelledMail;
use App\Policies\CancelOrderPolicy;
use App\Policies\ConfirmBankTransferPolicy;
use App\Policies\IsOrderLockedPolicy;
use App\Policies\ShipmentPollingPolicy;
use App\Services\Econt\EcontService;
use App\Services\Shipment\ShipmentCancellationService;
use App\Services\Shipment\ShipmentReturnService;
use App\Services\Shipment\ShipmentTrackingSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $record = $this->getRecord();

        if (! $record) {
            return;
        }

        $changed = app(ShipmentTrackingSyncService::class)->syncShipmentTracking($record);

        if ($changed) {
            $this->refreshUi();
        }
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
                    $this->record->updateQuietly([
                        'status' => OrderStatus::CANCELLED->value,
                    ]);

                    $shipment = $this->record->shipment;
                    if ($shipment && ! empty($shipment->carrier_shipment_id)) {
                        if (config('services.econt.enabled')) {
                            try {
                                $deleteResponse = app(EcontService::class)->deleteLabels([$shipment->carrier_shipment_id]);
                                Log::info('Econt delete label success', [
                                    'order_id' => $this->record->id,
                                    'shipment_id' => $shipment->id,
                                    'carrier_shipment_id' => $shipment->carrier_shipment_id,
                                    'response' => $deleteResponse,
                                ]);
                                app(ShipmentCancellationService::class)->clearCancelledShipmentData($shipment);
                            } catch (\Throwable $e) {
                                Log::error('Econt delete label failed', [
                                    'order_id' => $this->record->id,
                                    'shipment_id' => $shipment->id,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Неуспешно анулиране на товарителницата в Еконт')
                                    ->body('Поръчката беше отказана локално, но товарителницата не можа да бъде анулирана в Еконт.')
                                    ->send();
                            }
                        } else {
                            app(ShipmentCancellationService::class)->clearCancelledShipmentData($shipment, [
                                'error_message' => 'Еконт е изключен в локалната среда.',
                            ]);
                        }
                    } elseif ($shipment) {
                        app(ShipmentCancellationService::class)->clearCancelledShipmentData($shipment);
                    }

                    if ($this->record->customer_email) {
                        Mail::to($this->record->customer_email)->send(new OrderCancelledMail($this->record->id));
                    }

                    Notification::make()
                        ->success()
                        ->title('Поръчката е отказана')
                        ->body('Клиентът беше уведомен по имейл.')
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
                        app(ShipmentReturnService::class)->createReturnLabel($this->record);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Неуспешно създаване на обратна товарителница в Еконт')
                            ->body('Заявката за връщане не беше изпратена към Еконт. Проверете данните по пратката за подробности.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Заявено е връщане')
                        ->body('Създадена е обратна пратка и е пусната към Еконт по стандартния shipment flow.')
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
