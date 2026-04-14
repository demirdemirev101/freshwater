<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Schemas\CategoryInfolist;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Категории';
     protected static ?string $modelLabel = 'категория';
    protected static ?string $pluralModelLabel = 'Категории';


    /* ===============================
     | Access
     =============================== */
    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('view categories');
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->can('view categories');
    }

    /* ===============================
     | CRUD
     =============================== */
    public static function canCreate(): bool
    {
        return Auth::user()->can('create categories');
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->can('edit categories');
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete categories');
    }
    //==============================

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
