<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $resetUrl = url("/password/reset/{$this->token}?email=" . urlencode($this->user->email));

        return $this->subject('Đặt lại mật khẩu')
            ->view('emails.reset-password', ['url' => $resetUrl]);
    }
}
