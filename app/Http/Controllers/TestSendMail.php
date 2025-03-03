<?php

use App\Mail\MailerSend;
use Illuminate\Support\Facades\Mail;

Mail::to('dungtq@stringee.com')->send(new MailerSend());
