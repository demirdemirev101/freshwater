<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\OrderItem;
use App\Services\OrderItemService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Поръчани артикули';
    protected static ?string $modelLabel = 'артикул';
    protected static ?string $pluralModelLabel = 'Поръчани артикули';

    private function isLocked(): bool
    {
        $order = $this->getOwnerRecord();

        if (! $order) {
            return true;
        }

        return ! ($order->status === 'pending_review'
            || ($order->payment_method === 'bank_transfer' && $order->payment_status !== 'paid'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Продукт')
                    ->relationship('product', 'name')
                    ->required()
                    ->preload(),

                TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Продукт')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Общо')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        return app(OrderItemService::class)->create([
                            'order_id' => $this->ownerRecord->id,
                            'product_id' => $data['product_id'],
                            'quantity' => $data['quantity'],
                        ]);
                    })
                    ->after(function ($livewire) {
                        $livewire->dispatch('orderUpdated');
                    })
                    ->visible(fn () => ! $this->isLocked()),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (OrderItem $record, array $data) {
                        return app(OrderItemService::class)->update($record, [
                            'quantity' => $data['quantity'],
                        ]);
                    })
                    ->after(fn ($livewire) => $livewire->dispatch('orderUpdated'))
                    ->visible(fn () => ! $this->isLocked()),
                DeleteAction::make()
                    ->action(function (OrderItem $record) {
                        app(OrderItemService::class)->delete($record);
                    })
                    ->after(fn ($livewire) => $livewire->dispatch('orderUpdated'))
                    ->visible(fn () => ! $this->isLocked()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->action(function ($records) {
                        $service = app(OrderItemService::class);

                        foreach ($records as $record) {
                            $service->delete($record);
                        }
                    })
                    ->after(fn ($livewire) => $livewire->dispatch('orderUpdated'))
                    ->visible(fn () => ! $this->isLocked()),
            ]);
    }
}
