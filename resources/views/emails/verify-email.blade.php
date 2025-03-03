<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Xác nhận tài khoản</title>
</head>
<body>
<h2>Chào {{ $user->name }},</h2>
<p>Cảm ơn bạn đã đăng ký tài khoản tại {{ config('app.name') }}.</p>
<p>Vui lòng nhấp vào nút dưới đây để xác nhận email:</p>

<a href="{{ $url }}"
   style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
    Xác nhận tài khoản
</a>

<p>Nếu bạn không đăng ký tài khoản, vui lòng bỏ qua email này.</p>
<p>Liên kết này sẽ hết hạn sau 60 phút.</p>

<p>Trân trọng,<br> {{ config('app.name') }} Team</p>
</body>
</html>
