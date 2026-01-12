<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Поръчки';

    /* ===============================
     | Access
     =============================== */
    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('view orders');
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->can('view orders');
    }

    /* ===============================
     | CRUD
     =============================== */
    public static function canCreate(): bool
    {
        return false; // ❌ никога от админ
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('edit orders');
    }

    public static function canDelete($record): bool
    {
        return false; // ❌ никога
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
    //======================================

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
