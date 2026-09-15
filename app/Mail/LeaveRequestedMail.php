<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Leave $leave
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle demande de congé à valider',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-requested',
        );
    }
}