<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 // 🚚 Delivery settings
                Toggle::make('delivery_enabled')
                    ->label('Активна доставка')
                    ->default(true)
                    ->reactive(),
                TextInput::make('free_delivery_over')
                    ->label('Безплатна доставка над')
                    ->numeric()
                    ->prefix('€ ')
                    ->nullable()
                    ->helperText('Остави празно, ако няма безплатна доставка')
                    ->disabled(fn ($get) => $get('delivery_enabled') === false),
            ]);
    }
}
