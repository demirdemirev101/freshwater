<h2>Благодарим за поръчката!</h2>

<p>Здравей, {{ $order->customer_name }},</p>

<p>
    Получихме твоята поръчка с номер
    <strong>#{{ $order->id }}</strong>.
</p>

<hr>

<h3>📦 Поръчани продукти</h3>

<table width="100%" cellpadding="5">
    @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product_name }}</td>
            <td align="center">x{{ $item->quantity }}</td>
            <td align="right">{{ number_format($item->total_price, 2) }} лв.</td>
        </tr>
    @endforeach
</table>

<hr>

<p><strong>Доставка:</strong> {{ number_format($order->shipping_price, 2) }} лв.</p>
<p><strong>Общо:</strong> {{ number_format($order->total_price, 2) }} лв.</p>

<hr>

<h3>🚚 Адрес за доставка</h3>
<p>{{ $order->shipping_address }}</p>

<p>Ще се свържем с теб при нужда.</p>

<p>
    Поздрави,<br>
    <strong>Freshwater.bg</strong>
</p>
