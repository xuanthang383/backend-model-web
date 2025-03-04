<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Verification</title>
</head>
<body>
<h2>Hello {{ $user->name }},</h2>
<p>Thank you for signing up at {{ config('app.name') }}.</p>
<p>Please click the button below to verify your email address:</p>

<a href="{{ $url }}"
   style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
    Verify Account
</a>

<p>If you did not sign up for an account, please ignore this email.</p>
<p>This verification link will expire in 60 minutes.</p>

<p>Best regards,<br>The {{ config('app.name') }} Team</p>
</body>
</html>
