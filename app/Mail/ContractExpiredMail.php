<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contract $contract
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contrat expiré - Action requise',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-expired',
        );
    }
}