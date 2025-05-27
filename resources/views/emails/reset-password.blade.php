<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>
<h2>Hello {{ $user->name }},</h2>
<p>We received a request to reset the password for your account.</p>

<p>Click the button below to reset your password:</p>

<a href="{{ $url }}"
   style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;">
    Reset Password
</a>

<p>If you did not request a password res
