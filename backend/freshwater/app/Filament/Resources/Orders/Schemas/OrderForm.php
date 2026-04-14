<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $isLocked = fn ($record) => $record
            && ! ($record->status === 'pending_review'
                || ($record->payment_method === 'bank_transfer' && $record->payment_status !== 'paid'));

        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Потребител')
                    ->nullable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            return;
                        }

                        $user = User::find($state);

                        if (! $user) {
                            return;
                        }

                        $set('customer_name', $user->name);
                        $set('customer_email', $user->email);
                        $set('customer_phone', $user->phone);
                    })
                    ->visible(fn ($record) => (bool) $record->user_id)
                    ->disabled($isLocked),

                TextInput::make('customer_name')
                    ->label('Име на клиента')
                    ->disabled(fn ($record, $get) => $isLocked($record) || (bool) $get('user_id')),

                TextInput::make('customer_email')
                    ->label('Имейл')
                    ->disabled(fn ($record, $get) => $isLocked($record) || (bool) $get('user_id')),

                TextInput::make('customer_phone')
                    ->label('Телефон')
                    ->tel()
                    ->disabled(fn ($record, $get) => $isLocked($record) || (bool) $get('user_id')),

                TextInput::make('shipping_address')
                    ->label('Адрес за доставка')
                    ->required()
                    ->disabled($isLocked),

                TextInput::make('shipping_city')
                    ->label('Град за доставка')
                    ->required()
                    ->disabled($isLocked),

                TextInput::make('shipping_postcode')
                    ->label('Пощенски код')
                    ->disabled($isLocked),

                Select::make('status')
                    ->label('Статус на поръчката')
                    ->options(
                        collect(OrderStatus::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->toArray()
                    )
                    ->required()
                    ->default(OrderStatus::PENDING->value)
                    ->native(false)
                    ->preload()
                    ->disabled(),

                TextInput::make('subtotal')
                    ->label('Междинна сума')
                    ->numeric()
                    ->prefix('EUR ')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('shipping_price')
                    ->label('Цена за доставка')
                    ->prefix('EUR ')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('total')
                    ->label('Обща сума')
                    ->numeric()
                    ->prefix('EUR ')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('payment_method')
                    ->label('Метод на плащане')
                    ->options(
                        collect(PaymentMethod::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->toArray()
                    )
                    ->required()
                    ->default(PaymentMethod::COD->value)
                    ->native(false)
                    ->preload()
                    ->disabled(),

                Select::make('payment_status')
                    ->label('Статус на плащане')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->toArray()
                    )
                    ->required()
                    ->default(PaymentStatus::PENDING->value)
                    ->native(false)
                    ->preload()
                    ->disabled(),

                Textarea::make('notes')
                    ->label('Бележки')
                    ->disabled(),
            ]);
    }
}
