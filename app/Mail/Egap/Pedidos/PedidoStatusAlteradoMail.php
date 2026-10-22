<?php

namespace App\Mail\Egap\Pedidos;

use App\Models\egap\Almoxarifado\ItemPedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoStatusAlteradoMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ItemPedido $item,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido Online - Patrimônio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'egap.mail.pedidos.pedido-status-alterado',
        );
    }
}
