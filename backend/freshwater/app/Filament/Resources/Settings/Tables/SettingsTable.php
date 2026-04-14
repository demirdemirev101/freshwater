<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

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
                TextColumn::make('free_delivery_over')
                    ->label('Безплатна доставка над')
                    ->money('EUR', 0.00)
                    ->disabled(fn ($get) => $get('delivery_enabled') === false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn ($record) => SettingResource::canEdit($record)),
                DeleteAction::make()
                    ->authorize(fn ($record) => SettingResource::canDelete($record)),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
