<x-filament-panels::page>
    <div wire:poll.60s="pollShipmentStatus">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
