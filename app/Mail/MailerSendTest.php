<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailerSendTest extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->subject('Test MailerSend API')
            ->view('emails.test');
    }
}
