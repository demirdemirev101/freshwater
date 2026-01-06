<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('delivery_enabled')
                    ->label('Активна доставка')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('delivery_price')
                    ->label('Цена за доставка')
                    ->money('BGN')
                    ->default(0.00)
                    ->disabled(fn ($get) => $get('delivery_enabled') === false),
                TextColumn::make('free_delivery_over')
                    ->label('Безплатна доставка над')
                    ->money('BGN')
                    ->disabled(fn ($get) => $get('delivery_enabled') === false),
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
