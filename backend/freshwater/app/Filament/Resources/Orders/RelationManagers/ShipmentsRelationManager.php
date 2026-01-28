<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Events\OrderReadyForShipment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipment';
    protected static ?string $title = 'Доставка';
    protected static ?string $recordTitleAttribute = 'tracking_number';

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                TextInput::make('tracking_number')
                    ->label('Tracking Number')
                    ->disabled(),
                
                TextInput::make('weight')
                    ->label('Тегло (kg)')
                    ->numeric()
                    ->step(0.001),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'created' => 'Създаден',
                        'pending' => 'Чака',
                        'confirmed' => 'Потвърден',
                        'picked_up' => 'Взет',
                        'in_transit' => 'В транспорт',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                    ]),
            ]);
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
                    ->label('Tracking')
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
                Action::make('view')
                    ->label('Виж')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ShipmentResource::getUrl('view', ['record' => $record])),

                Action::make('download_label')
                    ->label('Етикет')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => $record->label_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->label_url)),
            ])
            ->emptyStateHeading('Няма доставка')
            ->emptyStateIcon('heroicon-o-truck');
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}