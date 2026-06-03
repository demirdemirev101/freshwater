<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

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
            ->poll(fn (): ?string => $this->shouldPollShipments() ? '5s' : null)
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('carrier')
                    ->label('Куриер')
                    ->badge()
                    ->color('info'),

                TextColumn::make('direction')
                    ->label('Посока')
                    ->badge()
                    ->colors([
                        'primary' => 'outbound',
                        'warning' => 'return',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state === 'return' ? 'Връщане' : 'Изпращане'),

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
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Създаден',
                        'pending' => 'Чака',
                        'confirmed' => 'Потвърден',
                        'picked_up' => 'Взет',
                        'in_transit' => 'В транспорт',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                        'returning' => 'Връща се към подателя',
                        'returned' => 'Върнат на подателя',
                        'cancelled' => 'Отменен',
                        null, '' => '—',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime('d.m.Y H:i'),

                TextColumn::make('expected_delivery_at')
                    ->label('Очаквана дата на доставка')
                    ->state(fn ($record) => $this->resolveExpectedDeliveryAt($record->carrier_response))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('download_label')
                    ->label('Етикет')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record): ?string => $record->label_url)
                    ->openUrlInNewTab()
                    ->authorize(fn ($record): bool => ! empty($record->label_url)
                        && $record->status !== 'cancelled'
                        && Auth::user()->can('view shipments')),
            ])
            ->emptyStateHeading('Няма доставки')
            ->emptyStateIcon('heroicon-o-truck');
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

    private function shouldPollShipments(): bool
    {
        $order = $this->getOwnerRecord();

        if (! $order) {
            return false;
        }

        return $order->shipments()
            ->whereIn('status', ['created', 'pending', 'confirmed', 'in_transit', 'returning'])
            ->exists();
    }
}
