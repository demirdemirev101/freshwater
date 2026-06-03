<x-emails.layout
    title="Ново съобщение от формата за контакт"
    subtitle="Получено е ново запитване от сайта Freshwater."
>
    <div style="margin:0 0 24px;padding:20px 22px;background-color:#ffffff;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Име:</strong> {{ $name }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Имейл:</strong> {{ $email }}</div>
        <div style="margin-bottom:10px;font-size:14px;line-height:1.7;color:#18426b;"><strong>Телефон:</strong> {{ $phone }}</div>
    </div>

    <div style="padding:20px 22px;background-color:#f7fbfd;border:1px solid #d8e7f1;border-radius:18px;">
        <div style="margin:0 0 12px;font-size:14px;font-weight:700;color:#1f67ae;text-transform:uppercase;letter-spacing:0.4px;">Съобщение</div>
        <div style="font-size:14px;line-height:1.8;color:#18426b;white-space:pre-line;">{{ $messageContent }}</div>
    </div>
</x-emails.layout>
