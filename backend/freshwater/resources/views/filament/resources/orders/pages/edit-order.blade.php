<x-filament-panels::page>
    <div @if ($this->shouldPollShipmentStatus()) wire:poll.60s="pollShipmentStatus" @endif>
        {{ $this->content }}
    </div>
</x-filament-panels::page>
