<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;

class RelatedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedProducts';

    protected static ?string $title = 'Свързани продукти';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('primaryImage.image_path')
                    ->label('Снимка')
                    ->disk('public')
                    ->square(),

                TextColumn::make('name')
                    ->label('Име')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('BGN')
                    ->placeholder('-'),

                TextColumn::make('sale_price')
                    ->label('Цена с отстъпка')
                    ->money('BGN')
                    ->placeholder('-'),

                IconColumn::make('stock')
                    ->label('Наличност')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Добави продукт')
                    ->recordSelectSearchColumns(['name', 'slug'])
                    ->preloadRecordSelect()
                    //->successRedirectUrl(fn() => url()->current())
                    //->recordSelectOptionsQuery(fn (Builder $query) => $query->where('id', '!=', $this->getOwnerRecord()->getKey())),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Премахни'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
