<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Resources\Shipments\Widgets\ShipmentTimeline;
use App\Services\Econt\EcontService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_label')
                ->label('Изтегли етикет')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => $this->record->label_url)
                ->openUrlInNewTab()
                ->visible(fn () => !empty($this->record->label_url)),
            Action::make('refresh_status')
                ->label('Обнови статус')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    try {
                        $econtService = app(EcontService::class);
                        $response = $econtService->trackShipment($this->record->tracking_number);

                        // Обработка на статуса
                        if (!empty($response['shipments'])) {
                            $shipmentData = $response['shipments'][0];
                            
                            $events = $shipmentData['trackingEvents'] ?? [];
                            $latestEvent = end($events);

                            if ($latestEvent) {
                                $this->record->update([
                                    'tracking_events' => $events,
                                    'status' => $this->mapEcontStatus($latestEvent['statusCode']),
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Статусът е обновен')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to refresh shipment status', [
                            'shipment_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Грешка при обновяване')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => !empty($this->record->tracking_number)),
            Action::make('view_order')
                ->label('Виж поръчка')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.orders.view', $this->record->order_id))
                ->visible(fn () => $this->record->order_id),
            DeleteAction::make()
                ->visible(fn () => in_array($this->record->status, ['created', 'error'])),
        ];
    }

    protected function mapEcontStatus(string $econtStatus): string
    {
        return match ($econtStatus) {
            'registered' => 'confirmed',
            'prepared_for_shipment', 'collected_from_client' => 'picked_up',
            'in_delivery_network', 'on_the_way' => 'in_transit',
            'delivered_to_office', 'delivered_to_address' => 'delivered',
            'returned' => 'returned',
            default => 'in_transit',
        };
    }

     public function getRelationManagers(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ShipmentTimeline::class,
        ];
    }
}
