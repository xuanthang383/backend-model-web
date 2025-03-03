<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
</head>
<body>
<h2>Xin chào {{ $user->name }},</h2>
<p>Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>

<p>Nhấp vào nút bên dưới để đặt lại mật khẩu:</p>

<a href="{{ $url }}"
   style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;">
    Đặt lại mật khẩu
</a>

<p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
<p><strong>Lưu ý:</strong> Liên kết này sẽ hết hạn sau 60 phút.</p>

<p>Trân trọng,<br>
    Đội ngũ hỗ trợ <strong>{{ config('app.name') }}</strong></p>
</body>
</html>
