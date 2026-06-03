<x-emails.layout
    title="Грешка при изчисляване на доставка"
    subtitle="Възникна проблем при изчисляване на доставката за поръчка с банков превод."
>
    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Поръчка:</strong> #{{ $orderId }}</div>
        <div style="font-size:14px;line-height:1.7;color:#18426b;"><strong>Грешка:</strong> {{ $errorMessage }}</div>
    </div>

    <p style="margin:0;font-size:14px;line-height:1.7;color:#5b7899;">Моля, проверете настройките на Еконт и опитайте отново.</p>
</x-emails.layout>
