{{-- resources/views/emails/layouts/security.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
</head>
<body style="margin:0; padding:0; background-color:#f6f8f7; font-family:Arial, Helvetica, sans-serif; color:#0f1f26;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f8f7;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px; width:100%;">
                    <tr>
                        <td style="background-color:#093240; border-bottom:4px solid #0baf1a; padding:22px 28px; border-radius:8px 8px 0 0;">
                            <div style="font-size:18px; font-weight:bold; color:#ffffff;">Archive Repository</div>
                            <div style="font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#a9bcb5; margin-top:4px;">Lukefield Finance Company Limited</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff; padding:28px; border:1px solid #e3e9e6; border-top:none; font-size:15px; line-height:1.6;">
                            @yield('body')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px; font-size:12px; line-height:1.5; color:#5b6b72; text-align:center;">
                            This is an automated message from the Archive Repository. Do not reply to this email.<br>
                            Lukefield staff will never ask you for your password or sign-in code.<br>
                            &copy; {{ date('Y') }} Lukefield Finance Company Limited
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
