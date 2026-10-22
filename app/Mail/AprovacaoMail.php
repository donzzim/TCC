<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AprovacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $conteudo;
    public string $formUrl;
    public int $timeout = 100;
    public int $tries = 1;

    public function __construct(array $conteudo, string $formUrl)
    {
        Log::info('Inicializando AprovacaoMail', [
            'conteudo' => $conteudo,
        ]);
        $this->conteudo = $conteudo;
        $this->formUrl = $formUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aprovação Confirmada',
        );
    }

    public function content(): Content
    {
        Log::info('Enviando e-mail de Aprovacao', [
            'conteudo' => $this->conteudo,
        ]);
        return new Content(
            view: 'emails.aprovacao',
            with: [
                'conteudo' => $this->conteudo,
                'formUrl' => $this->formUrl
            ]
        );
    }


    public function attachments(): array
    {
        return [];
    }
}
