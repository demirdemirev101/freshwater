<h2>Благодарим за поръчката!</h2>

<p>Здравей, {{ $order->customer_name }},</p>

<p>
    Получихме твоята поръчка с номер
    <strong>#{{ $order->id }}</strong>.
</p>

<hr>

<h3>📦 Поръчани продукти</h3>

<ul>
    @foreach($order->items as $item)
        <li>{{ $item->product_name }} x {{ $item->quantity }}</li>
        <li>{{ number_format($item->total, 2) }} € / {{ number_format($item->total*1.9558, 2) }} лв.</li>
    @endforeach
</ul>

<hr>

<p><strong>Доставка:</strong> {{ number_format($order->shipping_price, 2) }} € / {{ number_format($order->shipping_price*1.9558, 2) }} лв.</p>
<p><strong>Общо:</strong> {{ number_format($order->total, 2) }} € / {{ number_format($order->total*1.9558, 2) }} лв.</p>

<hr>

@if ($order->payment_method === 'bank_transfer')
    <h3>🏦 Плащане по банков превод</h3>
    <p><strong>Получател:</strong> {{ config('services.bank_transfer.company_name') }}</p>
    <p><strong>IBAN:</strong> {{ config('services.bank_transfer.iban') }}</p>
    <p><strong>Банка:</strong> {{ config('services.bank_transfer.bank_name') }}</p>
    <p><strong>BIC:</strong> {{ config('services.bank_transfer.bic') }}</p>
    <p><strong>Сума:</strong>  {{ number_format($order->total, 2) }} {{ config('services.bank_transfer.currency') }} / {{ number_format($order->total*1.9558, 2) }} BGN</p>
    <p><strong>Основание:</strong> Поръчка #{{ $order->id }}</p>
    <p>След потвърждаване на плащането ще подготвим и изпратим пратката.</p>
    <hr>
@endif

<h3>🚚 Адрес за доставка</h3>
<p>{{ $order->shipping_address }}</p>
<p>{{ $order->shipping_city }}</p>

<p>Ще се свържем с теб при нужда.</p>

<p>
    Поздрави,<br>
    <strong>Freshwater.bg</strong>
</p>
