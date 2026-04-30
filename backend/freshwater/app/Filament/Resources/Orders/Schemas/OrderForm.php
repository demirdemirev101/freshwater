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
                    ->disabled(),

                TextInput::make('customer_name')
                    ->label('Име на клиента')
                    ->disabled(),

                TextInput::make('customer_email')
                    ->label('Имейл')
                    ->disabled(),

                TextInput::make('customer_phone')
                    ->label('Телефон')
                    ->tel()
                    ->disabled(),

                TextInput::make('shipping_city')
                    ->label('Град за доставка')
                    ->required()
                    ->disabled(),

                TextInput::make('econt_office_code')
                    ->label('Код на офис Еконт')
                    ->required(fn($get) => $get('shipping_method') !== 'address')
                    ->visible(fn($get) => $get('shipping_method') !== 'address')
                    ->disabled(),

                TextInput::make('shipping_address')
                    ->label(fn ($get) => $get('shipping_method') === 'address'
                        ? 'Адрес за доставка'
                        : 'Офис на Еконт'
                    )
                    ->visible()
                    ->disabled(),

                TextInput::make('shipping_postcode')
                    ->label('Пощенски код')
                    ->visible(fn($get) => $get('econt_office_code' !== null))
                    ->required(fn($get) => $get('econt_office_code' !== null))
                    ->disabled(),

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
