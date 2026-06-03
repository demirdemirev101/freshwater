<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use App\Models\Shipment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

        if (! isset($this->table)) {
            return;
        }

        $this->resetTable();
    }

    public function getTable(): Table
    {
        if (! isset($this->table)) {
            $this->bootedInteractsWithTable();
        }

        return parent::getTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->tableRecords())
            ->columns([
                TextColumn::make('carrier')
                    ->label('Куриер')
                    ->badge()
                    ->color('info'),

                TextColumn::make('entry_type')
                    ->label('Вид')
                    ->badge()
                    ->color(fn (Shipment $record): string => $record->entry_type_key === 'return' ? 'warning' : 'primary'),

                TextColumn::make('tracking_number')
                    ->label('Номер за проследяване')
                    ->copyable()
                    ->copyMessage('Копирано!')
                    ->placeholder('-'),

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
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Създаден',
                        'pending' => 'Чака',
                        'confirmed' => 'Потвърден',
                        'picked_up' => 'Взет',
                        'in_transit' => 'В транспорт',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                        'returning' => 'Връща се към подателя',
                        'returned' => 'Върната към подателя',
                        'cancelled' => 'Анулирана',
                        null, '' => '-',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime('d.m.Y H:i'),

                TextColumn::make('expected_delivery_at')
                    ->label('Очаквана дата на доставка')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('download_label')
                    ->label('Етикет')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Shipment $record): ?string => $record->label_url)
                    ->openUrlInNewTab()
                    ->authorize(fn (Shipment $record): bool => ! empty($record->label_url)
                        && $record->status !== 'cancelled'
                        && Auth::user()->can('view shipments')),
            ])
            ->emptyStateHeading('Няма доставка')
            ->emptyStateIcon('heroicon-o-truck');
    }

    public function getTableRecordKey(Model | array $record): string
    {
        if ($record instanceof Shipment && filled($record->table_row_key ?? null)) {
            return (string) $record->table_row_key;
        }

        return parent::getTableRecordKey($record);
    }

    private function tableRecords(): Collection
    {
        $shipment = $this->getOwnerRecord()?->shipment;

        if (! $shipment instanceof Shipment) {
            return collect();
        }

        $records = collect([
            $this->makeOutgoingRecord($shipment),
        ]);

        if (! empty($shipment->return_tracking_number)
            || ! empty($shipment->return_status)
            || ! empty($shipment->return_label_url)
            || ! empty($shipment->return_carrier_shipment_id)
        ) {
            $records->push($this->makeReturnRecord($shipment));
        }

        return $records;
    }

    private function makeOutgoingRecord(Shipment $shipment): Shipment
    {
        $record = clone $shipment;

        $record->forceFill([
            'table_row_key' => 'shipment-'.$shipment->getKey().'-outgoing',
            'entry_type' => 'Изпращане',
            'entry_type_key' => 'outgoing',
            'expected_delivery_at' => $this->resolveExpectedDeliveryAt($shipment->carrier_response),
        ]);

        return $record;
    }

    private function makeReturnRecord(Shipment $shipment): Shipment
    {
        $record = clone $shipment;

        $record->forceFill([
            'table_row_key' => 'shipment-'.$shipment->getKey().'-return',
            'entry_type' => 'Връщане',
            'entry_type_key' => 'return',
            'tracking_number' => $shipment->return_tracking_number,
            'status' => $shipment->return_status,
            'created_at' => $shipment->return_sent_to_carrier_at ?? $shipment->created_at,
            'expected_delivery_at' => $this->resolveExpectedDeliveryAt($shipment->return_carrier_response),
            'label_url' => $shipment->return_label_url,
        ]);

        return $record;
    }

    private function resolveExpectedDeliveryAt(?array $carrierResponse): mixed
    {
        $value =
            data_get($carrierResponse, 'tracking.shipmentStatuses.0.status.deliveryDate')
            ?? data_get($carrierResponse, 'tracking.shipmentStatuses.0.status.expectedDeliveryDate')
            ?? data_get($carrierResponse, 'label.deliveryTime')
            ?? data_get($carrierResponse, 'label.expectedDeliveryDate');

        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value);
        }

        return $value;
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
