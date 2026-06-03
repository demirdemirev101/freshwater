<x-emails.layout
    title="Неуспешно създаване на пратка"
    subtitle="Системата не успя да изпрати пратката към Еконт и е нужна проверка."
>
    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Поръчка:</strong> #{{ $shipment->order_id }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Клиент:</strong> {{ $shipment->order?->customer_name ?? 'Няма' }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Имейл:</strong> {{ $shipment->order?->customer_email ?? 'Няма' }}</div>
        <div style="font-size:14px;line-height:1.7;color:#18426b;"><strong>Грешка:</strong> {{ $shipment->error_message ?? 'Няма записано съобщение за грешка.' }}</div>
    </div>

    <a
        href="{{ $adminOrderUrl }}"
        style="display:inline-block;padding:12px 20px;border-radius:999px;background:linear-gradient(90deg,#24b39b 0%,#1f67ae 100%);color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;"
    >
        Отвори поръчката
    </a>
</x-emails.layout>
