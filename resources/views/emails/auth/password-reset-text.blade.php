{{ $appName }} - Reset your password

Hello {{ $recipientName }},

We received a request to reset the password for your administrator account. Use this link to choose a new password:
{!! $resetUrl !!}

This link expires in {{ $expiresIn }} minutes. For your security, don’t share it with anyone.

If you didn’t request a password reset, you can safely ignore this email.
