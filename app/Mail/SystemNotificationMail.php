<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $title,
        public ?string $body,
        public ?string $type = null,
        public array $data = [],
        public ?string $recipientName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->type ? '['.strtoupper($this->type).'] '.$this->title : $this->title;

        return new Envelope(
            subject: str($subject)->limit(150)->toString(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.system-notification',
            with: [
                'title' => $this->title,
                'body' => $this->body,
                'type' => $this->type,
                'data' => $this->data,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}