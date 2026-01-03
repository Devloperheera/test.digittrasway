<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OTPResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $user;

    public function __construct($otp, $user)
    {
        $this->otp = $otp;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset OTP - DigiTransway Admin Portal',
            from: env('MAIL_FROM_ADDRESS', '25gtconnect@gmail.com'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-reset-password',
            with: [
                'otp' => $this->otp,
                'user' => $this->user,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
