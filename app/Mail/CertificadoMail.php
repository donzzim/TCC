<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CertificadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $conteudo;
    public int $timeout = 100;
    public int $tries = 1;

    public function __construct(array $conteudo)
    {
        Log::info('Inicializando CertificadoMail', [
            'conteudo' => $conteudo,
        ]);
        $this->conteudo = $conteudo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Certificados Disponíveis',
        );
    }

    public function content(): Content
    {
        Log::info('Enviando e-mail de Certificados', [
            'conteudo' => $this->conteudo,
        ]);
        return new Content(
            view: 'emails.certificado',
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
