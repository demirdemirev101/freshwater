@php
    $trackingUrl = null;

    if (! empty(config('services.econt.track_url')) && ! empty($trackingNumber)) {
        $trackingUrl = rtrim(config('services.econt.track_url'), '/');
    }
@endphp

<x-emails.layout
    title="{{ $mailTitle ?? 'Пратката е изпратена' }}"
    subtitle="{{ $mailSubtitle ?? 'Пратката ви е създадена в Еконт и вече може да бъде проследена.' }}"
>
    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="font-size:14px;line-height:1.7;color:#18426b;">
            <strong>Номер на пратка:</strong> {{ $trackingNumber ?? 'Няма' }}
        </div>
    </div>

    @if (! empty($trackingUrl))
        <a
            href="{{ $trackingUrl }}"
            style="display:inline-block;padding:12px 20px;border-radius:999px;background:linear-gradient(90deg,#24b39b 0%,#1f67ae 100%);color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;"
        >
            {{ $buttonLabel ?? 'Проследи пратката' }}
        </a>
    @endif
</x-emails.layout>
