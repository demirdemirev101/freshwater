<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipment';
    protected static ?string $title = 'Доставка';
    protected static ?string $recordTitleAttribute = 'tracking_number';

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
                        'secondary' => 'created',
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => ['picked_up', 'in_transit'],
                        'success' => 'delivered',
                        'danger' => 'error',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'created' => 'Създаден',
                        'pending' => 'Чака',
                        'confirmed' => 'Потвърден',
                        'picked_up' => 'Взет',
                        'in_transit' => 'В транспорт',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('download_label')
                    ->label('Етикет')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => $record->label_url)
                    ->openUrlInNewTab()
                    ->visible(fn () => $this->getOwnerRecord()?->status !== 'cancelled')
                    ->authorize(fn ($record) => !empty($record->label_url) && Auth::user()->can('view shipments')),
            ])
            ->emptyStateHeading('Няма доставка')
            ->emptyStateIcon('heroicon-o-truck');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord->status === 'cancelled') {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
