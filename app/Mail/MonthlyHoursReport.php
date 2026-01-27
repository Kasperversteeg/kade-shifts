<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyHoursReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $users, public string $month)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Monthly Hours Report - {$this->month}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.monthly-report',
            with: [
                'users' => $this->users,
                'month' => $this->month,
                'grandTotal' => collect($this->users)->sum('total_hours'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
