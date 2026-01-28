<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\RelatedProductsRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Продукти';
    protected static ?string $modelLabel = 'продукт';
    protected static ?string $pluralModelLabel = 'Продукти';

    /* ===============================
     | Resource visibility (sidebar)
     =============================== */
    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('view products');
    }

    /* ===============================
     | CRUD permissions
     =============================== */
    public static function canCreate(): bool
    {
        return Auth::user()?->can('create products');
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('edit products');
    }
    public static function canDelete($record): bool
    {
        return Auth::user()?->can('delete products');
    }
    public static function canView($record): bool
    {
        return Auth::user()?->can('view products');
    }
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('view products');
    }
    //=============================
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            RelatedProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
