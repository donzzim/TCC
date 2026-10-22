<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LembreteMail extends Mailable
{
    use Queueable, SerializesModels;

    public  $conteudo;
    public int $timeout = 100;
    public int $tries = 1;

    public function __construct($conteudo)
    {
        Log::info('Inicializando LembreteMail', [
            'conteudo' => $conteudo,
        ]);
        $this->conteudo = $conteudo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lembrete de Curso',
        );
    }

    public function content(): Content
    {
        Log::info('Enviando e-mail de lembrete', [
            'conteudo' => $this->conteudo,
        ]);
        return new Content(
            view: 'emails.cursolembrete',
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
