<?php

namespace App\Mail\Egap\Patrimonio;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmacaoSolicitacaoInclusaoBemMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $bem
     */
    public function __construct(
        public readonly array $bem,
        public readonly string $setor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmação de Solicitação no Sistema e-GAP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'egap.mail.patrimonio.confirmacao-inclusao-bem',
        );
    }
}
