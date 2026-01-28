<?php

namespace App\Filament\Resources\Shipments;

use App\Filament\Resources\Shipments\Pages\ManageShipments;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Shipment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Доставки';
    protected static ?string $modelLabel = 'доставка';
    protected static ?string $pluralModelLabel = 'Доставки';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required()
                    ->searchable(),
                TextInput::make('tracking_number')
                    ->label('Tracking Number')
                    ->disabled(),
                TextInput::make('weight')
                    ->label('Тегло (kg)')
                    ->numeric()
                    ->step(0.001),
                TextInput::make('cash_on_delivery')
                    ->label('Наложен платеж')
                    ->numeric()
                    ->prefix('лв'),
                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'created' => 'Създаден',
                        'pending' => 'Чака изпращане',
                        'confirmed' => 'Потвърден',
                        'picked_up' => 'Взет от куриер',
                        'in_transit' => 'В транспорт',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('order.id')
                    ->label('Поръчка')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => $record->order 
                        ? route('filament.admin.resources.orders.view', $record->order) 
                        : null),
                TextColumn::make('tracking_number')
                    ->label('Tracking')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Копирано!')
                    ->placeholder('—'),
                TextColumn::make('carrier')
                    ->label('Куриер')
                    ->badge()
                    ->color('info'),
                TextColumn::make('weight')
                    ->label('Тегло')
                    ->suffix(' kg')
                    ->sortable(),    
                TextColumn::make('cash_on_delivery')
                    ->label('Наложен платеж')
                    ->money('BGN')
                    ->sortable(),
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
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'created' => 'Създаден',
                        'pending' => 'Чака',
                        'confirmed' => 'Потвърден',
                        'delivered' => 'Доставен',
                        'error' => 'Грешка',
                    ]),
                SelectFilter::make('carrier')
                    ->label('Куриер')
                    ->options([
                        'econt' => 'Еконт',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download_label')
                    ->label('Етикет')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => $record->label_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->label_url)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageShipments::route('/'),
            'view' => ViewShipment::route('/{record}'),
        ];
    }
}
