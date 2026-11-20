<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Reset your password</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f6f8; color:#202124; font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f6f8; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%; max-width:600px;">
                    <tr>
                        <td align="center" style="padding:8px 16px 24px;">
                            <span style="display:inline-block; width:42px; height:42px; line-height:42px; border-radius:12px; background-color:#ff5500; color:#ffffff; font-size:24px; font-weight:700; text-align:center;">G</span>
                            <div style="padding-top:10px; color:#202124; font-size:17px; font-weight:700;">{{ $appName }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; border:1px solid #e5e7eb; border-radius:14px; background-color:#ffffff;">
                            <h1 style="margin:0 0 20px; color:#202124; font-size:23px; line-height:1.3;">Reset your password</h1>
                            <p style="margin:0 0 16px; color:#4b5563; font-size:15px; line-height:1.6;">Hello {{ $recipientName }},</p>
                            <p style="margin:0 0 24px; color:#4b5563; font-size:15px; line-height:1.6;">We received a request to reset the password for your administrator account. Use the button below to choose a new password.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="border-radius:8px; background-color:#ff5500;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block; padding:14px 28px; border:1px solid #ff5500; border-radius:8px; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none;">Reset password</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px; color:#4b5563; font-size:14px; line-height:1.6;">This link expires in {{ $expiresIn }} minutes. For your security, don’t share it with anyone.</p>
                            <p style="margin:0 0 24px; color:#4b5563; font-size:14px; line-height:1.6;">If you didn’t request a password reset, you can safely ignore this email.</p>
                            <div style="border-top:1px solid #e5e7eb; padding-top:18px;">
                                <p style="margin:0 0 8px; color:#6b7280; font-size:12px; line-height:1.5;">If the button doesn’t work, copy and paste this link into your browser:</p>
                                <a href="{{ $resetUrl }}" style="color:#c2410c; font-size:12px; line-height:1.5; word-break:break-all;">{{ $resetUrl }}</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 16px 8px; color:#9ca3af; font-size:12px; line-height:1.5;">
                            This is an automated security email from {{ $appName }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
