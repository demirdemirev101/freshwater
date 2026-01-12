<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Име')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->placeholder('-')
                    ->money('BGN')
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Цена с отстъпка')
                    ->placeholder('-')
                    ->money('BGN')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Наличност')
                    ->searchable()
                    ->sortable(),
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
                ViewAction::make()
                    ->visible(fn () => Auth::user()->can('view products')),
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('edit products')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('delete products')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => Auth::user()->can('delete products')),
                ]),
            ]);
    }
}
