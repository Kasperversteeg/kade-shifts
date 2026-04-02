<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractExpiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $employee)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Contract {$this->employee->name} loopt binnenkort af",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contract-expiry',
            with: [
                'employeeName' => $this->employee->name,
                'endDate' => $this->employee->contract_end_date->format('d-m-Y'),
                'daysRemaining' => now()->diffInDays($this->employee->contract_end_date),
                'url' => route('admin.user-detail', $this->employee),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
