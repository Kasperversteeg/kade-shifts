<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SchedulePublished extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $shifts,
        public string $weekStart,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Je rooster voor de week van {$this->weekStart}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.schedule-published',
            with: [
                'user' => $this->user,
                'shifts' => $this->shifts,
                'weekStart' => $this->weekStart,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
