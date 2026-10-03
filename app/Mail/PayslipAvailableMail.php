<?php

namespace App\Mail;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payslip $payslip
    ) {}

    public function envelope(): Envelope
    {
        $month = \Carbon\Carbon::create($this->payslip->payPeriod->year, $this->payslip->payPeriod->month)->translatedFormat('F Y');
        
        return new Envelope(
            subject: "Votre bulletin de paie de {$month}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payslip-available',
        );
    }
}