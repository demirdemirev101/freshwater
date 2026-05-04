<?php

namespace App\Filament\Resources\Messages;

use App\Filament\Resources\Messages\Pages\ManageMessages;
use App\Models\Contact;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class MessagesResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

        protected static ?string $navigationLabel = 'Съобщения';
        protected static ?string $modelLabel = 'Съобщение';
        protected static ?string $pluralModelLabel = 'Съобщения';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Име')
                    ->disabled(),
                TextInput::make('email')
                    ->label('Имейл')
                    ->disabled(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->disabled(),
                Textarea::make('message')
                    ->label('Съобщение')
                    ->disabled(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Име')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Имейл')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Съобщение')
                    ->limit(50)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Дата на създаване')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Изтрий'),
                ViewAction::make()
                    ->label('Преглед'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Изтрий избраните'),
                ])
                ->label('Групови действия'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMessages::route('/'),
        ];
    }
}
