<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Име')
                    ->required(),
                Select::make('category_id')
                    ->label('Категории')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->required(),
                TextInput::make('slug')
                    ->hidden()
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                RichEditor::make('short_description')
                    ->label('Кратко описание')
                    ->columnSpanFull(),
                RichEditor::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                Section::make('Цена и наличност')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->prefix('BGN'),
                        TextInput::make('sale_price')
                            ->label('Цена с отстъпка')
                            ->numeric()
                            ->prefix('BGN'),
                        Toggle::make('stock')
                            ->label('Наличност'),
                        ])
                ])
                    ->columnSpan('2'),
                RichEditor::make('extra_information')
                    ->label('Допълнителна информация')
                    ->columnSpanFull(),
            ]);
    }
}
