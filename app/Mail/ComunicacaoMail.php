<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComunicacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $conteudo;
    public int $timeout = 100;
    public int $tries = 1;

    public function __construct(string $conteudo)
    {
        $this->conteudo = $conteudo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comunicação Importante',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comunicacao',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
