<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Verification</title>
</head>
<body>
<p>Thank you for signing up at {{ config('app.name') }}.</p>
<p>Please click the button below to verify your email address:</p>

<a href="{{ $actionUrl }}"
   style="display: inline-block; background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold; text-align: center;">
    Verify Account
</a>

<p>If you did not sign up for an account, please ignore this email.</p>
<p>This verification link will expire in 60 minutes.</p>

<p>Best regards,<br>The {{ config('app.name') }} Team</p>
</body>
</html>
