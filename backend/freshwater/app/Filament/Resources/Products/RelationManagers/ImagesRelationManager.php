<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Изображения';

    protected function afterSave(): void
    {
        $this->ownerRecord->refresh();
    }

    public static function canViewRecord($ownerRecord): bool
    {
        return Auth::user()->can('view product images');
    }
    protected function canEdit($record): bool
    {
        return Auth::user()->can('edit product images');
    }

    protected function canCreate(): bool
    {
        return Auth::user()->can('create product images');
    }

    protected function canDelete($record): bool
    {
        return Auth::user()->can('delete product images');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('is_primary')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                IconColumn::make('is_primary')
                    ->label('Основно изображение')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Ред на изображението')
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('image_path')
                    ->label('Изображение')
                    ->disk('public'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Качи изображение')
                    ->authorize(fn () => ImagesRelationManager::canCreate($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактирай')
                    ->authorize(fn () => ImagesRelationManager::canEdit($this->getOwnerRecord())),
                DeleteAction::make()
                    ->label('Изтрий')
                    ->authorize(fn () => ImagesRelationManager::canDelete($this->getOwnerRecord())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Изтрий избраните')
                        ->authorize(fn () => ImagesRelationManager::canDelete($this->getOwnerRecord())),
                ]),
            ]);
    }
}
