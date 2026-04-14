<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
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
                    ->state(fn ($record) => OrderStatus::tryFrom($record->status)?->label() ?? $record->status)
                    ->badge()
                    ->color(fn ($record) => match ($record->status) {
                        'pending', 'pending_review' => 'warning',
                        'processing' => 'info',
                        'ready_for_shipment', 'shipped' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'return_requested' => 'warning',
                        'returned' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Обща сума')
                    ->money('EUR', 0.00)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Статус на плащане')
                    ->state(fn ($record) => PaymentStatus::tryFrom($record->payment_status)?->label() ?? $record->payment_status)
                    ->badge()
                    ->color(fn ($record) => match ($record->payment_status) {
                        'pending' => 'warning',
                        'unpaid' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Метод на плащане')
                    ->state(fn ($record) => match ($record->payment_method) {
                        'cod' => 'Наложен платеж',
                        'bank_transfer' => 'Банков превод',
                        default => $record->payment_method,
                    })
                    ->badge()
                    ->color(fn ($record) => match ($record->payment_method) {
                        'cod' => 'info',
                        'bank_transfer' => 'primary',
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
                EditAction::make()
                    ->authorize(fn ($record) => Auth::user()->can('edit orders')
                        && ($record->status === 'pending_review'
                            || ($record->payment_method === 'bank_transfer' && $record->payment_status !== 'paid'))),
                DeleteAction::make()
                    ->authorize(fn ($record) => Auth::user()->can('delete orders')
                        && ($record->status === 'pending_review'
                            || ($record->payment_method === 'bank_transfer' && $record->payment_status !== 'paid'))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn (? \Illuminate\Database\Eloquent\Model $record) => $record
                            ? (Auth::user()->can('delete orders')
                                && ($record->status === 'pending_review'
                                    || ($record->payment_method === 'bank_transfer' && $record->payment_status !== 'paid')))
                            : Auth::user()->can('delete orders')),
                ]),
            ]);
    }
}
