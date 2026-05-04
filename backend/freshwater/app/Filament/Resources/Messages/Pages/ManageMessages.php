<?php

namespace App\Filament\Resources\Messages\Pages;

use App\Filament\Resources\Messages\MessagesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMessages extends ManageRecords
{
    protected static string $resource = MessagesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
