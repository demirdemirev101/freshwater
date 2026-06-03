<x-emails.layout
    title="Заявено връщане на поръчка"
    subtitle="Получихме заявка за връщане на поръчка #{{ $order->id }}."
>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#18426b;">Здравей, {{ $order->customer_name }},</p>

    <div style="margin:0 0 24px;padding:20px 22px;background-color:#f7fbfd;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin:0 0 14px;font-size:14px;font-weight:700;color:#1f67ae;text-transform:uppercase;letter-spacing:0.4px;">Поръчани продукти</div>
        @foreach($order->items as $item)
            <div style="padding:10px 0;{{ ! $loop->last ? 'border-bottom:1px solid #e4eef5;' : '' }}">
                <div style="font-size:15px;font-weight:600;color:#18426b;">{{ $item->product_name }} x {{ $item->quantity }}</div>
                <div style="margin-top:4px;font-size:14px;color:#5b7899;">{{ number_format($item->total, 2) }} € / {{ number_format($item->total*1.9558, 2) }} лв.</div>
            </div>
        @endforeach
    </div>

    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="display:flex;justify-content:space-between;gap:16px;font-size:17px;font-weight:700;color:#1bb8bf;">
            <span>Общо</span>
            <span>{{ number_format($order->total, 2) }} € / {{ number_format($order->total*1.9558, 2) }} лв.</span>
        </div>
    </div>

    <p style="margin:0;font-size:14px;line-height:1.7;color:#5b7899;">Ще се свържем с теб с инструкции за връщането.</p>
</x-emails.layout>
