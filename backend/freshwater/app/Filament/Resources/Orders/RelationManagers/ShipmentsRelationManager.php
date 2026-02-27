<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipment';
    protected static ?string $title = 'Доставка';
    protected static ?string $recordTitleAttribute = 'tracking_number';

    #[On('orderUpdated')]
    public function refreshFromOrderUpdate(): void
    {
        $this->getOwnerRecord()?->refresh();

        // важно: typed property може още да не е инициализирана
        if (! isset($this->table)) {
            return;
        }

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('carrier')
                    ->label('Куриер')
                    ->badge()
                    ->color('info'),

                TextColumn::make('tracking_number')
                    ->label('Номер за проследяване')
                    ->copyable()
                    ->copyMessage('Копирано!')
                    ->placeholder('—'),

                TextColumn::make('weight')
                    ->label('Тегло')
                    ->suffix(' kg'),

                TextColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'primary' => ['picked_up', 'in_transit'],
                        'secondary' => ['created', 'returned'],
                        'warning' => ['pending', 'returning'],
                        'info' => 'confirmed',
                        'success' => 'delivered',
                        'danger' => ['error', 'cancelled'],
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'created' => 'Създаден',
                        'pending' => 'Чака',
                        'confirmed' => 'Потвърден',
                        'picked_up' => 'Взет',
                        'in_transit' => 'В транспорт',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                        'returning' => 'Returning to sender',
                        'returned' => 'Returned to sender',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime('d.m.Y H:i'),

                /**
                 * The expected_delivery_at column is customized to extract the expected delivery date from the carrier_response JSON field.
                 * It checks if the expectedDeliveryDate is a numeric timestamp and converts it to a Carbon date instance, otherwise it displays the raw value.
                 * If the carrier_response or expectedDeliveryDate is not available, it shows a placeholder.
                 */
                TextColumn::make('expected_delivery_at')
                    ->label('Очаквана дата на доставка')
                    ->getStateUsing(function ($record) {
                        $value =
                            data_get($record->carrier_response, 'tracking.shipmentStatuses.0.status.deliveryDate')
                            ?? data_get($record->carrier_response, 'tracking.shipmentStatuses.0.status.expectedDeliveryDate')
                            ?? data_get($record->carrier_response, 'label.deliveryTime')
                            ?? data_get($record->carrier_response, 'label.expectedDeliveryDate');

                        if (is_numeric($value)) {
                            return Carbon::createFromTimestampMs((int) $value);
                        }

                        return $value;
                    })
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),

            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                /**
                 * The download_label action is customized to allow users to download the shipping label if it's available.
                 * It checks if the label_url is present and if the user has permission to view shipments before displaying the action.
                 * The action opens the label URL in a new tab when clicked. It is only visible for shipments that are not cancelled.
                 * The authorization logic ensures that only users with the appropriate permissions can access the shipment label,
                 * and the visibility logic ensures that the action is not shown for cancelled shipments, providing a better user experience and security.
                 */
                Action::make('download_label')
                    ->label('Етикет')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => $record->label_url)
                    ->openUrlInNewTab()
                    ->authorize(fn ($record) => !empty($record->label_url) 
                    && $record->status !== 'cancelled' && Auth::user()->can('view shipments')),
            ])
            ->emptyStateHeading('Няма доставка')
            ->emptyStateIcon('heroicon-o-truck');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord->status === OrderStatus::CANCELLED->value) {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
