<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ChatKitClientAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $userEmail,
        public int $agentId,
        public string $agentName,
        public string $source,
        public string $message,
        public string $referer,
        public Carbon $reportedAt,
    ) {}

    public function envelope(): Envelope
    {
        $app = (string) config('app.name', 'App');
        $preview = trim(preg_replace('/\s+/', ' ', $this->message) ?? '');
        if (mb_strlen($preview) > 72) {
            $preview = mb_substr($preview, 0, 69).'…';
        }

        return new Envelope(
            subject: "[{$app}] ChatKit — {$this->source}".($preview !== '' ? " — {$preview}" : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.chatkit-client-alert',
        );
    }
}
