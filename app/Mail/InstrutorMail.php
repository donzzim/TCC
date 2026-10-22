<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InstrutorMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $conteudo;
    public int $timeout = 100;
    public int $tries = 1;

    public function __construct($conteudo)
    {
        Log::info('Inicializando InstrutorMail', [
            'conteudo' => $conteudo,
        ]);
        $this->conteudo = $conteudo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Informativo para Instrutor',
        );
    }

    public function content(): Content
    {
        Log::info('Enviando e-mail para instrutor', [
            'conteudo' => $this->conteudo,
        ]);
        return new Content(
            view: 'emails.instrutor',
            with: [
                'conteudo' => $this->conteudo,
            ]
        );
    }



    public function attachments(): array
    {
        return [];
    }
}
