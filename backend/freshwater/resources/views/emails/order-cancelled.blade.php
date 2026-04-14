<h2>Поръчката е отказана</h2>

<p>Здравей, {{ $order->customer_name }},</p>

<p>
    Поръчката ти с номер <strong>#{{ $order->id }}</strong> беше отказана.
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
<hr>

<p><strong>Общо:</strong>{{ number_format($order->total, 2) }} €  / {{ number_format($order->total*1.9558, 2) }} лв.</p>
<hr>

<p>Ако имаш въпроси, свържи се с нас.</p>

<p>
    Поздрави,<br>
    <strong>Freshwater.bg</strong>
</p>
