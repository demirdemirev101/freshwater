<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Потребители';

    /* ===============================
     | Access
     =============================== */
    public static function canAccess(): bool
    {
        return Auth::user()->can('view users');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view users');
    }

    /* ===============================
     | CRUD
     =============================== */
    public static function canCreate(): bool
    {
        return Auth::user()->can('create users');
    }

    public static function canEdit(Model $record): bool
    {
        // ❌ admin не може да редактира superadmin
        if ($record->hasRole('superadmin')) {
            return false;
        }

        return Auth::user()->can('edit users');
    }

    public static function canDelete(Model $record): bool
    {
        // ❌ никой не трие superadmin
        if ($record->hasRole('superadmin')) {
            return false;
        }

        return Auth::user()->can('delete users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Име')
                    ->required(),
                TextInput::make('email')
                    ->label('Email адрес')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Телефонен номер')
                    ->tel(),
                Select::make('roles')
                    ->label('Роля')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Име')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email адрес'),
                TextColumn::make('roles')
                    ->label('Роля')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->roles->first()?->name)
                    ->color(fn ($state) => match ($state) {
                        'customer' => 'info',
                        'admin' => 'primary',
                        'superadmin' => 'success',
                        default => 'gray',
                    }),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (Model $record) => UserResource::canEdit($record)),
                DeleteAction::make()
                    ->authorize(fn (Model $record) => UserResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => UserResource::canDeleteAny()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
