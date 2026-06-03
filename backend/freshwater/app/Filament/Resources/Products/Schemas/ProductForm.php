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
                Section::make('Цени, размери и наличност')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('price')
                                ->label('Цена')
                                ->numeric()
                                ->prefix('€ '),
                            TextInput::make('sale_price')
                                ->label('Цена с отстъпка')
                                ->numeric()
                                ->prefix('€ '),
                            TextInput::make('weight')
                                ->label('Тегло (кг)')
                                ->numeric()
                                ->suffix('кг'),
                            TextInput::make('height')
                                ->label('Височина (см)')
                                ->numeric()
                                ->suffix('см'),
                            TextInput::make('width')
                                ->label('Ширина (см)')
                                ->numeric()
                                ->suffix('см'),
                            TextInput::make('length')
                                ->label('Дължина (см)')
                                ->numeric()
                                ->suffix('см'),
                        ]),

                        Toggle::make('stock')
                            ->label('Следи наличност')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === false) {
                                    // 🔥 когато не следим наличност
                                    $set('quantity', null);
                                } else {
                                    // 🔥 когато включим следене
                                    $set('quantity', 0);
                                }
                            }),

                        TextInput::make('quantity')
                            ->label('Количество')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->visible(fn ($get) => $get('stock') === true),
                    ])
                    ->columnSpanFull(),
                RichEditor::make('extra_information')
                    ->label('Допълнителна информация')
                    ->columnSpanFull(),
            ]);
    }
}
