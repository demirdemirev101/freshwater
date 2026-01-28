<x-mail::message>
# Пратката е изпратена

Пратката ви е създадена в Еконт.

Номер на пратка: **{{ $trackingNumber ?? 'N/A' }}**

@if (!empty($labelUrl))
<x-mail::button :url="$labelUrl">
Изтегли товарителница
</x-mail::button>
@endif

Благодарим,<br>
{{ config('app.name') }}
</x-mail::message>
