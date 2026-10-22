<?php

namespace App\Mail\Egap\Patrimonio;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitacaoInclusaoBemMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $bem
     */
    public function __construct(
        public readonly array $bem,
        public readonly string $solicitante,
        public readonly string $setor,
        public readonly ?string $emailSolicitante = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitação de Inclusão de Bem no Sistema e-GAP',
            replyTo: $this->emailSolicitante ? [$this->emailSolicitante] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'egap.mail.patrimonio.solicitacao-inclusao-bem',
        );
    }
}
