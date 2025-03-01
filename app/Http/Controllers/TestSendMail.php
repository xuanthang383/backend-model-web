<?php

use App\Mail\MailerSendTest;
use Illuminate\Support\Facades\Mail;

Mail::to('dungtq@stringee.com')->send(new MailerSendTest());
