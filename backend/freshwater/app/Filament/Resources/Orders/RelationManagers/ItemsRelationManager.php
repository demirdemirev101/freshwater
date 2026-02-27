<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
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
use Livewire\Attributes\On;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Поръчани артикули';
    protected static ?string $modelLabel = 'Артикул';
    protected static ?string $pluralModelLabel = 'Поръчани артикули';

   
    /* 
    * This method is used to determine if the order is locked for editing items. 
    * An order is considered locked if its status is not 'pending_review' or if it's a bank transfer
    */
    #[On('orderUpdated')]
    public function refreshFromOrderUpdate(): void
    {
        $this->getOwnerRecord()?->refresh();
    }

    private function isLocked(): bool
    {
        $order = $this->getOwnerRecord();

        if (! $order) {
            return true;
        }

        return ! ($order->status === OrderStatus::PENDING_REVIEW->value 
        || ($order->payment_method === 'bank_transfer' && $order->payment_status !== PaymentStatus::PAID->value));
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
                /* 
                * The CreateAction is customized to use the OrderItemService for creating new order items
                * and it dispatches an 'orderUpdated' event after creation to allow the parent order page to refresh its data.
                */
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
                /*
                * The EditAction is customized to use the OrderItemService for updating existing order items. 
                * It updates the quantity of the order item and dispatches an 'orderUpdated' event after the update.
                * The DeleteAction is also customized to use the OrderItemService for deleting order items and dispatching the same event after deletion.
                */
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
                /**
                 * The DeleteBulkAction is customized to use the OrderItemService for deleting multiple order items at once.
                 * It iterates through the selected records and deletes each one using the service, then dispatches an 'orderUpdated' 
                 * event after the bulk deletion to refresh the parent order page data.
                 */
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
