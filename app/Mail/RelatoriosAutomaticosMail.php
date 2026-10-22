<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RelatoriosAutomaticosMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{filename: string, content_base64: string, label: string}>  $reports
     */
    public function __construct(
        public string $cursoTitulo,
        public array $reports,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Relatorios automaticos: {$this->cursoTitulo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.relatorios-automaticos',
            with: [
                'cursoTitulo' => $this->cursoTitulo,
                'reports' => $this->reports,
            ],
        );
    }

    public function attachments(): array
    {
        return collect($this->reports)
            ->map(fn(array $report): Attachment => Attachment::fromData(
                fn(): string => base64_decode($report['content_base64'], true) ?: '',
                $report['filename'],
            )->withMime('application/pdf'))
            ->values()
            ->all();
    }
}
