<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderReadyForShipment;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderReturnRequestedMail;
use App\Policies\CancelOrderPolicy;
use App\Policies\ConfirmBankTransferPolicy;
use App\Policies\IsOrderLockedPolicy;
use App\Policies\ShipmentPollingPolicy;
use App\Services\Econt\EcontService;
use App\Services\Shipment\ShipmentCancellationService;
use App\Services\Shipment\ShipmentTrackingSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;
    protected string $view = 'filament.resources.orders.pages.edit-order';

    /*
     * Refreshes the order data from the database and updates the form. This is useful after performing actions that change the order status or
     *  shipment information, to ensure the UI reflects the latest state.
     */
    public function handleOrderRefresh(): void
    {
        $this->getRecord()->refresh();

        $this->fillForm();
    }
    /**
     * Refreshes the order data and dispatches a UI refresh. This is used after actions that change the order or shipment status,
     *  to ensure the UI is up to date.
     */
    private function refreshUi(): void
    {
        $this->handleOrderRefresh();
        $this->dispatch('$refresh');
        $this->dispatch('orderUpdated');
    }

    /**
     * Determines whether or not to continue polling the shipment status from Econt. This is based on the current order status and shipment information.
     */
    public function shouldPollShipmentStatus(): bool
    {
        $record = $this->getRecord()->fresh(['shipment']);

        if (! $record) {
            return false;
        }

        return app(ShipmentPollingPolicy::class)->shouldPollShipmentStatus($record);
    }

    /**
     * Getting fresh instance of the shipment from the econt api and updating the shipment and order status accordingly. This method is called
     *  when the "Poll Shipment Status" action is triggered and it uses the ShipmentTrackingSyncService to perform the synchronization.
     *  If the shipment status has changed, it refreshes the UI to reflect the latest information.
     */
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

    /**
     * Defines the header actions for the order edit page. This includes actions for confirming cash on delivery orders, confirming bank transfers,
     *  cancelling orders, and requesting returns. Each action has specific visibility conditions based on the order's payment method, payment status
     *  and order status, to ensure that only relevant actions are shown to the admin user. The actions also include confirmation modals to prevent
     *  accidental clicks and they perform the necessary updates to the order and shipment records,
     *  as well as sending notifications and emails to the customer when appropriate.
     */
    protected function getHeaderActions(): array
    {
        return [
            // The "Confirm COD" action is visible for orders that are paid via cash on delivery and are in the "pending review" status.
            // When confirmed, it updates the order status to "ready for shipment", triggers the OrderReadyForShipment event and sends a notification
            //  to the admin user confirming that the order has been confirmed and will be sent to Econt.
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
            // The "Confirm Bank Transfer" action is visible for orders that are paid via bank transfer, are not yet marked as paid
            //  and are in a status that indicates they are still being processed. When confirmed, it updates the order's payment status to "paid"
            //  and the order status to "ready for shipment", triggers the OrderReadyForShipment event and sends a notification to the admin user
            //  confirming that the payment has been confirmed and the order will be sent to Econt.
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
            // The "Cancel Order" action is visible for orders that are in a status that allows cancellation and do not have a return request. When confirmed, it updates the order status to "cancelled",
            //  attempts to cancel the label in Econt if there is an associated shipment with a carrier shipment ID, clears the shipment data locally, sends an order cancellation email to the customer
            //  and a notification to the admin user confirming that the order has been cancelled.
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
                                    ->title('Econt label cancel failed')
                                    ->body('The order was cancelled locally, but the label could not be cancelled in Econt.')
                                    ->send();
                            }
                        } else {
                           app(ShipmentCancellationService::class)->clearCancelledShipmentData($shipment, [
                                'error_message' => 'Econt disabled (local environment)',
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
            // The "Request Return" action is visible for orders that are in a status that allows return requests and are not already cancelled,
            //  return requested, returned, or completed. When confirmed, it updates the order status to "return_requested",
            //  sends an order return requested email to the customer and a notification to the admin user confirming that the return has been requested.
            Action::make('request_return')
                ->label('Заяви връщане')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => app(CancelOrderPolicy::class)->canRequestReturn($this->record))
                ->requiresConfirmation()
                ->modalHeading('Заявка за връщане')
                ->modalDescription('Сигурни ли сте, че искате да заявите връщане на поръчката?')
                ->action(function () {
                    $this->record->updateQuietly([
                        'status' => OrderStatus::RETURN_REQUESTED->value,
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
    /**
     * Overrides the default form actions to conditionally hide them based on the order status and payment method.
     *  If the order is locked (not in a status that allows editing), the form actions will be hidden to prevent any edits.
     *  This ensures that only relevant actions are available to the admin user based on the current state of the order.
     */
    protected function getFormActions(): array
    {
        if ($this->record && app(IsOrderLockedPolicy::class)->isLocked($this->record)) {
            return [];
        }

        return parent::getFormActions();
    }
}
