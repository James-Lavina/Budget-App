<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} verification code</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:{{ $brandColor }};padding:20px 28px;color:#ffffff;font-size:18px;font-weight:700;">
                            {{ $appName }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px;font-size:16px;font-weight:700;">Hi {{ $name }},</p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
                                Use this code to finish creating your {{ $appName }} account.
                                It expires in {{ $minutes }} minutes.
                            </p>
                            <div style="text-align:center;margin:0 0 20px;">
                                <span style="display:inline-block;background:#f1f5f9;border-radius:12px;padding:14px 24px;font-size:32px;font-weight:800;letter-spacing:10px;color:{{ $brandColor }};">
                                    {{ $code }}
                                </span>
                            </div>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">
                                If you didn't try to sign up, you can ignore this email. Never share this code with anyone.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>