<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Leave $leave,
        public string $status
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->status === 'approved' 
            ? 'Votre demande de congé a été approuvée' 
            : 'Votre demande de congé a été rejetée';
            
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-status-changed',
        );
    }
}