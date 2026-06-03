<x-emails.layout
    title="Нова поръчка"
    subtitle="Има нова поръчка, която очаква преглед в административния панел."
>
    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Номер:</strong> #{{ $order->id }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Клиент:</strong> {{ $order->customer_name }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Имейл:</strong> {{ $order->customer_email }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>{{ $deliveryDetails['title'] }}:</strong> {{ $deliveryDetails['summary'] }}</div>
        <div style="font-size:15px;font-weight:700;color:#1bb8bf;"><strong>Сума:</strong> {{ number_format($order->subtotal, 2) }} € / {{ number_format($order->subtotal*1.9558, 2) }} лв.</div>
    </div>

    <a
        href="{{ $adminOrderUrl }}"
        style="display:inline-block;padding:12px 20px;border-radius:999px;background:linear-gradient(90deg,#24b39b 0%,#1f67ae 100%);color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;"
    >
        Отвори поръчката в админ панела
    </a>
</x-emails.layout>
