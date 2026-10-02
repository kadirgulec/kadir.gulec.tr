{{-- Contact form e-mail in the notebook's colors. Inline styles: e-mail clients ignore stylesheets. --}}
<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="color-scheme" content="light">
        <title>İletişim formu: {{ $senderName }}</title>
    </head>
    <body style="margin: 0; padding: 0; background: #f1e9d8; color: #2b2420; font-family: 'Nunito Sans', 'Segoe UI', Helvetica, Arial, sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f1e9d8; padding: 24px 12px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background: #fbf7ee; border-radius: 3px; border-left: 6px solid #26386b;">
                        <tr>
                            <td style="padding: 28px 28px 8px;">
                                <p style="margin: 0; font-family: 'Caveat', 'Comic Sans MS', cursive; font-size: 24px; font-weight: bold; color: #26386b;">kg · iletişim formu</p>
                                <p style="margin: 6px 0 0; font-size: 14px; color: #5e5249;">
                                    {{ $senderName }} · <a href="mailto:{{ $senderEmail }}" style="color: #5e5249;">{{ $senderEmail }}</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 28px 20px; border-top: 1px dashed #e4dac8; font-size: 16px; line-height: 1.6;">
                                {!! nl2br(e($body)) !!}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 28px 28px; border-top: 1px dashed #e4dac8; font-size: 12px; line-height: 1.6; color: #74675a;">
                                kadir.gulec.tr'nin Hakkımda sayfasındaki formdan geldi. Cevapla dersen {{ $senderEmail }} adresine gider.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
