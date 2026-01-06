<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('user_id')
                    ->label('Тип')
                    ->state(fn ($record) => $record->user_id ? 'Профил' : 'Гост')
                    ->badge()
                    ->color(fn ($state) => $state === 'Профил' ? 'success' : 'warning'),
                TextColumn::make('customer_name')
                    ->label('Име на клиента')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Телефонен номер')
                    ->searchable(),
               TextColumn::make('status')
                    ->label('Статус на поръчката')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'в очакване' => 'warning',
                        'обработва се' => 'info',
                        'изпратена' => 'primary',
                        'завършена' => 'success',
                        'отменена' => 'danger',
                        'върната' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Обща сума')
                    ->money('BGN', 0.00)
                    ->sortable(),
                 TextColumn::make('payment_status')
                    ->label('Статус на плащане')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'unpaid' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Метод на плащане')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'cash' => 'info',
                        'bank_transfer' => 'primary',
                        'card' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
