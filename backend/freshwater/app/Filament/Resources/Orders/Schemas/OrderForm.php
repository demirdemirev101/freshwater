<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\enums\PaymentMethod;
use App\enums\PaymentStatus;
use App\enums\OrderStatus;
use App\Models\User;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
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
                    }),

                TextInput::make('customer_name')
                    ->disabled(fn ($get) => (bool) $get('user_id')),

                TextInput::make('customer_email')
                    ->disabled(fn ($get) => (bool) $get('user_id')),

                TextInput::make('customer_phone')
                    ->tel()
                    ->disabled(fn ($get) => (bool) $get('user_id')),

                TextInput::make('shipping_address')
                    ->label('Адрес за доставка')
                    ->required(),

                TextInput::make('shipping_city')
                    ->label('Град за доставка')
                    ->required(),

                TextInput::make('shipping_postcode')
                    ->label('Пощенски код'),

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
                    ->preload(),

                // 🔥 DERIVED FIELDS (READ ONLY)
                TextInput::make('subtotal')
                    ->label('Междинна сума')
                    ->numeric()
                    ->prefix('лв. ')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('shipping_price')
                    ->label('Цена за доставка')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('total')
                    ->label('Обща сума')
                    ->numeric()
                    ->prefix('лв. ')
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
                    ->default(PaymentMethod::CASH->value)
                    ->native(false)
                    ->preload(),

                Select::make('payment_status')
                    ->label('Статус на плащане')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->toArray()
                    )
                    ->required()
                    ->default(PaymentStatus::UNPAID->value)
                    ->native(false)
                    ->preload(),

                Textarea::make('notes')
                    ->label('Бележки'),
            ]);
    }
}


