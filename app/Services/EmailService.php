<?php

namespace App\Services;

use App\Mail\VerifyEmail;
use App\Mail\ResetPassword;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Gửi email xác nhận tài khoản
     */
    public function sendVerificationEmail($user, $url)
    {
        Mail::to($user->email)->send(new VerifyEmail($user, $url));
    }

    /**
     * Gửi email đặt lại mật khẩu
     */
    public function sendResetPasswordEmail($user, $token)
    {
        Mail::to($user->email)->send(new ResetPassword($user, $token));
    }
}
