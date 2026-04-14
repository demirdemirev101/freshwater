<x-mail::message>
# Пратката е изпратена

Пратката ви е създадена в Еконт.

Номер на пратка: **{{ $trackingNumber ?? 'N/A' }}**

@php
    $trackingUrl = null;
    if (!empty(config('services.econt.track_url')) && !empty($trackingNumber)) {
        $trackingUrl = rtrim(config('services.econt.track_url'), '/');
    }
@endphp

@if (!empty($trackingUrl))
<x-mail::button :url="$trackingUrl">
Проследи пратката
</x-mail::button>
@endif


Благодарим,<br>
{{ config('app.name') }}
</x-mail::message>
