<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
             CreateAction::make()
            ->label('Създай настройки')
            ->visible(fn () =>
                Auth::user()?->can('manage settings')
                && ! Setting::exists()
            )
            ->authorize(fn () =>
                Auth::user()?->can('manage settings')
            ),
        ];
    }
}
