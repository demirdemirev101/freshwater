<h2>Заявено връщане на поръчка</h2>

<p>Здравей, {{ $order->customer_name }},</p>

<p>
    Получихме заявка за връщане на поръчка
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

<p><strong>Общо:</strong> {{ number_format($order->total, 2) }} € / {{ number_format($order->total*1.9558, 2) }} лв.</p>

<hr>

<p>Ще се свържем с теб с инструкции за връщането.</p>

<p>
    Поздрави,<br>
    <strong>Freshwater.bg</strong>
</p>
