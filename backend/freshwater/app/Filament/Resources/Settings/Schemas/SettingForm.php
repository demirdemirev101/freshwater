<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Доставка')
                    ->schema([
                        Toggle::make('delivery_enabled')
                            ->label('Активна доставка')
                            ->default(true)
                            ->reactive(),
                        TextInput::make('free_delivery_over')
                            ->label('Безплатна доставка над')
                            ->numeric()
                            ->prefix('€ ')
                            ->nullable()
                            ->helperText('Остави празно, ако няма безплатна доставка.')
                            ->disabled(fn ($get) => $get('delivery_enabled') === false),
                    ]),
                Section::make('Плащания')
                    ->schema([
                        Toggle::make('stripe_enabled')
                            ->label('Stripe плащане')
                            ->default(false)
                            ->helperText('Включва картовите плащания през Stripe в checkout-а.'),
                    ]),
            ]);
    }
}
