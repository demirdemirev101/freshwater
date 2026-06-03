@props([
    'title',
    'subtitle' => null,
])
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f8fb;font-family:Arial,Helvetica,sans-serif;color:#18426b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f8fb;margin:0;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background-color:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 16px 40px rgba(22,73,117,0.12);">
                    <tr>
                        <td style="padding:20px 32px;background-color:#ffffff;border-bottom:1px solid #d8e7f1;">
                            <div style="font-size:15px;font-weight:700;letter-spacing:0.4px;color:#1bb8bf;text-transform:lowercase;">freshwater</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px;background:linear-gradient(90deg,#24b39b 0%,#1f67ae 100%);color:#ffffff;">
                            <div style="font-size:13px;opacity:0.9;letter-spacing:0.6px;text-transform:uppercase;">Freshwater</div>
                            <div style="margin-top:8px;font-size:30px;line-height:1.2;font-weight:700;">{{ $title }}</div>
                            @if (filled($subtitle))
                                <div style="margin-top:12px;font-size:15px;line-height:1.6;max-width:520px;color:rgba(255,255,255,0.92);">
                                    {{ $subtitle }}
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#f7fbfd;border-top:1px solid #d8e7f1;font-size:13px;line-height:1.7;color:#577698;">
                            Freshwater.bg<br>
                            Йонизатори и филтриране на вода
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
