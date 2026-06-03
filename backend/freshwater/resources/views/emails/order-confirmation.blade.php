<x-emails.layout
    title="Благодарим за поръчката!"
    subtitle="Получихме поръчка #{{ $order->id }} и ще я обработим възможно най-бързо."
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
        <div style="display:flex;justify-content:space-between;gap:16px;margin-bottom:10px;font-size:15px;color:#18426b;">
            <span><strong>Доставка</strong></span>
            <span>{{ number_format($order->shipping_price, 2) }} € / {{ number_format($order->shipping_price*1.9558, 2) }} лв.</span>
        </div>
        <div style="display:flex;justify-content:space-between;gap:16px;font-size:17px;font-weight:700;color:#1bb8bf;">
            <span>Общо</span>
            <span>{{ number_format($order->total, 2) }} € / {{ number_format($order->total*1.9558, 2) }} лв.</span>
        </div>
    </div>

    @if ($order->payment_method === 'bank_transfer')
        <div style="margin:0 0 24px;padding:20px 22px;background-color:#f7fbfd;border:1px solid #d8e7f1;border-radius:18px;">
            <div style="margin:0 0 14px;font-size:14px;font-weight:700;color:#1f67ae;text-transform:uppercase;letter-spacing:0.4px;">Плащане по банков превод</div>
            <div style="font-size:14px;line-height:1.8;color:#18426b;">
                <div><strong>Получател:</strong> {{ config('services.bank_transfer.company_name') }}</div>
                <div><strong>IBAN:</strong> {{ config('services.bank_transfer.iban') }}</div>
                <div><strong>Банка:</strong> {{ config('services.bank_transfer.bank_name') }}</div>
                <div><strong>BIC:</strong> {{ config('services.bank_transfer.bic') }}</div>
                <div><strong>Сума:</strong> {{ number_format($order->total, 2) }} {{ config('services.bank_transfer.currency') }} / {{ number_format($order->total*1.9558, 2) }} BGN</div>
                <div><strong>Основание:</strong> Поръчка #{{ $order->id }}</div>
            </div>
            <p style="margin:14px 0 0;font-size:14px;line-height:1.7;color:#5b7899;">След потвърждаване на плащането ще подготвим и изпратим пратката.</p>
        </div>
    @endif

    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin:0 0 14px;font-size:14px;font-weight:700;color:#1f67ae;text-transform:uppercase;letter-spacing:0.4px;">{{ $deliveryDetails['title'] }}</div>
        @foreach ($deliveryDetails['lines'] as $line)
            <div style="font-size:14px;line-height:1.7;color:#18426b;">{{ $line }}</div>
        @endforeach
    </div>

    <p style="margin:0;font-size:14px;line-height:1.7;color:#5b7899;">Ще се свържем с теб при нужда.</p>
</x-emails.layout>
