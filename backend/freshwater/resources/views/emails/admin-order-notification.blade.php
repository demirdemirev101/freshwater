<h2>📦 Нова поръчка</h2>

<p><strong>Номер:</strong> #{{ $order->id }}</p>
<p><strong>Клиент:</strong> {{ $order->customer_name }}</p>
<p><strong>Email:</strong> {{ $order->customer_email }}</p>
<p><strong>Град:</strong> {{ $order->shipping_city }}</p>
<p><strong>Сума:</strong> {{ number_format($order->total, 2) }} лв.</p>

<hr>

<p>Влез в админ панела за повече детайли.</p>
