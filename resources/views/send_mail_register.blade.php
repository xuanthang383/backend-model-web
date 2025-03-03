<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body>
<h2>Welcome, {{ $userName }}!</h2>
<p>Thank you for signing up at <strong>{{ config('app.name') }}</strong>.</p>

<h3>Your Account Details:</h3>
<p><strong>Email:</strong> {{ $userEmail }}</p>
<p><strong>Temporary Password:</strong> {{ $password }}</p>

<p>To log in, click the button below:</p>
<p><a href="{{ url('/login') }}" style="display: inline-block; padding: 10px 15px; background: #007BFF; color: #fff; text-decoration: none; border-radius: 5px;">Login Now</a></p>

<p><strong>Important:</strong> Please change your password immediately after logging in to ensure security.</p>

<p>If you didn’t register, please ignore this email or contact our support team.</p>

<p>Best regards,<br>
    <strong>{{ config('app.name') }} Team</strong></p>
</body>
</html>
