<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Events\OrderReadyForShipment;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderReturnRequestedMail;
use App\Services\Econt\EcontService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
    }

    public function pollShipmentStatus(): void
    {
        if (property_exists($this, 'mountedActions') && ! empty($this->mountedActions)) {
            return;
        }

        $record = $this->getRecord()->fresh(['shipment']);

        if (! $record) {
            return;
        }

        if (in_array($record->status, ['completed', 'cancelled', 'returned'], true)) {
            return;
        }

        $shipment = $record->shipment;

        if (! $shipment) {
            return;
        }

        if (empty($shipment->carrier_shipment_id)) {
            return;
        }

        if (! config('services.econt.enabled')) {
            return;
        }

        try {
            $response = app(EcontService::class)->trackShipment($shipment->carrier_shipment_id);
            $result = $response['shipmentStatuses'][0] ?? null;

            if (! is_array($result)) {
                return;
            }

            if (! empty($result['error'])) {
                Log::warning('Econt tracking error', [
                    'order_id' => $record->id,
                    'shipment_id' => $shipment->id,
                    'error' => $result['error'],
                ]);
                return;
            }

            $status = $result['status'] ?? null;

            if (! is_array($status)) {
                return;
            }

            $carrierResponse = $shipment->carrier_response;
            $carrierResponse = is_array($carrierResponse) ? $carrierResponse : [];
            $carrierResponse['tracking'] = $response;

            $updates = [
                'carrier_response' => $carrierResponse,
            ];

            $shortStatus = $status['shortDeliveryStatus'] ?? $status['shortDeliveryStatusEn'] ?? null;
            $trackingEvents = $status['trackingEvents'] ?? [];

            $delivered = ! empty($status['deliveryTime'])
                || in_array($shortStatus, ['Доставена', 'Delivered'], true);

            $inTransit = ! $delivered && (
                ! empty($trackingEvents)
                || in_array($shortStatus, [
                    'Приета в Еконт',
                    'Пътува по линия',
                    'В куриер',
                    'В офис',
                    'В офис на приемащ куриер',
                    'Приета в офис в офис на предаващ куриер',
                    'Пристигнала в офис',
                    'Постъпила за обработка в Логистичен център',
                    'Prepared in eEcont',
                    'Accepted in Econt',
                    'In route',
                    'In courier',
                    'In pick up courier',
                    'Accepted in office',
                    'In delivery courier\'s office',
                    'Arrived in office',
                    'Arrival departure from hub',
                ], true)
            );

            if ($delivered) {
                $updates['status'] = 'delivered';
            } elseif ($inTransit && $shipment->status !== 'delivered') {
                $updates['status'] = 'in_transit';
            }

            $shipment->fill($updates);
            $shipmentChanged = $shipment->isDirty();

            if ($shipmentChanged) {
                $shipment->save();
            }

            $orderChanged = false;

            if ($delivered && $record->status !== 'completed') {
                $record->update(['status' => 'completed']);
                $orderChanged = true;
            } elseif ($inTransit && $record->status !== 'shipped' && $record->status !== 'completed') {
                $record->update(['status' => 'shipped']);
                $orderChanged = true;
            }

            if ($shipmentChanged || $orderChanged) {
                $this->refreshUi();
            }
        } catch (\Throwable $e) {
            Log::error('Econt tracking failed', [
                'order_id' => $record->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function shouldPollShipmentStatus(): bool
    {
        $record = $this->getRecord()->fresh(['shipment']);

        if (! $record) {
            return false;
        }

        if (in_array($record->status, ['completed', 'cancelled', 'returned'], true)) {
            return false;
        }

        return ! empty($record->shipment?->carrier_shipment_id);
    }

    private function isLocked(): bool
    {
        $record = $this->record;

        if (! $record) {
            return true;
        }

        return ! ($record->status === 'pending_review'
            || ($record->payment_method === 'bank_transfer' && $record->payment_status !== 'paid'));
    }

    private function canCancelOrder(): bool
    {
        $record = $this->record;

        if (! $record) {
            return false;
        }

        if ($this->canRequestReturn()) {
            return false;
        }

        return in_array($record->status, ['pending', 'pending_review', 'processing'], true);
    }

    private function canRequestReturn(): bool
    {
        $record = $this->getRecord()->fresh(['shipment']);

        if (! $record) {
            return false;
        }

        if (! in_array($record->status, ['ready_for_shipment', 'shipped'], true)) {
            return false;
        }

        return ! in_array($record->status, ['cancelled', 'return_requested', 'returned'], true);
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
                        'status' => 'ready_for_shipment',
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

                    $this->refreshUi();
                }),
            Action::make('cancel_order')
                ->label('Откажи поръчка')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->canCancelOrder())
                ->requiresConfirmation()
                ->modalHeading('Отказ на поръчка')
                ->modalDescription('Сигурни ли сте, че искате да откажете поръчката?')
                ->action(function () {
                    $this->record->updateQuietly([
                        'status' => 'cancelled',
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
                                $shipment->update([
                                    'status' => 'cancelled',
                                    'label_url' => null,
                                    'carrier_payload' => null,
                                    'carrier_response' => null,
                                    'tracking_number' => null,
                                    'carrier_shipment_id' => null,
                                ]);
                            } catch (\Throwable $e) {
                                Log::error('Econt delete label failed', [
                                    'order_id' => $this->record->id,
                                    'shipment_id' => $shipment->id,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Econt label cancel failed')
                                    ->body('The order was cancelled locally, but the label could not be cancelled in Econt.')
                                    ->send();
                            }
                        } else {
                            $shipment->update([
                                'status' => 'cancelled',
                                'label_url' => null,
                                'carrier_payload' => null,
                                    'carrier_response' => null,
                                'tracking_number' => null,
                                'carrier_shipment_id' => null,
                                'error_message' => 'Econt disabled (local environment)',
                            ]);
                        }
                    } elseif ($shipment) {
                        $shipment->update([
                            'status' => 'cancelled',
                            'carrier_payload' => null,
                                    'carrier_response' => null,
                            'tracking_number' => null,
                            'carrier_shipment_id' => null,
                        ]);
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
                ->visible(fn () => $this->canRequestReturn())
                ->requiresConfirmation()
                ->modalHeading('Заявка за връщане')
                ->modalDescription('Сигурни ли сте, че искате да заявите връщане на поръчката?')
                ->action(function () {
                    $this->record->updateQuietly([
                        'status' => 'return_requested',
                    ]);

                    if ($this->record->customer_email) {
                        Mail::to($this->record->customer_email)->send(new OrderReturnRequestedMail($this->record->id));
                    }

                    Notification::make()
                        ->success()
                        ->title('Заявено е връщане')
                        ->body('Клиентът беше уведомен по имейл.')
                        ->send();

                    $this->refreshUi();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->isLocked()) {
            return [];
        }

        return parent::getFormActions();
    }
}
