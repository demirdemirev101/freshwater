<?php

namespace App\Filament\Resources\Shipments\Widgets;

use Filament\Widgets\Widget;

class ShipmentTimeline extends Widget
{
    protected string $view = 'filament.resources.shipments.widgets.shipment-timeline';

    public $record;

    protected int | string | array $columnSpan = 'full';

    public function mount($record): void
    {
        $this->record = $record;
    }
}
