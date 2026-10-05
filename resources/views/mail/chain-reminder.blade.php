{{-- Chain reminder in the notebook's colors. Inline styles: e-mail clients ignore stylesheets. --}}
<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="color-scheme" content="light">
        <title>Zincir hatırlatması</title>
    </head>
    <body style="margin: 0; padding: 0; background: #f1e9d8; color: #2b2420; font-family: 'Nunito Sans', 'Segoe UI', Helvetica, Arial, sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f1e9d8; padding: 24px 12px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background: #fbf7ee; border-radius: 3px; border-left: 6px solid #26386b;">
                        <tr>
                            <td style="padding: 28px 28px 8px;">
                                <p style="margin: 0; font-family: 'Caveat', 'Comic Sans MS', cursive; font-size: 24px; font-weight: bold; color: #26386b;">kg · zinciri kırma</p>
                            </td>
                        </tr>
                        @foreach ($lines as $line)
                            <tr>
                                <td style="padding: 14px 28px; border-top: 1px dashed #e4dac8;">
                                    <p style="margin: 0; font-size: 17px; font-weight: bold;">🔥 {{ $line['title'] }} <span style="font-size: 13px; font-weight: normal; color: #74675a;">· {{ $line['cadence'] }}</span></p>
                                    <p style="margin: 4px 0 0; font-size: 15px; line-height: 1.5; color: #5e5249;">{{ $line['text'] }}</p>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td style="padding: 16px 28px 28px; border-top: 1px dashed #e4dac8; font-size: 12px; line-height: 1.6; color: #74675a;">
                                Mazeretli günler de sayılır. Bu hatırlatma sadece sana gider, takipçiler görmez.
                                <a href="{{ $dashboardUrl }}" style="color: #74675a;">Panoda işaretle</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
