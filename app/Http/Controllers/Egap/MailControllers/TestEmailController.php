<?php

namespace App\Http\Controllers\Egap\MailControllers;

use App\Http\Controllers\Controller;
use App\Mail\Egap\TestMailEgap;
use Illuminate\Support\Facades\Mail;

class TestEmailController extends Controller
{
    public function sendEmail()
    {
        Mail::to('lucsantos@tjes.jus.br')->send(new TestMailEgap());

        return 'Email sent successfully';
    }
}
