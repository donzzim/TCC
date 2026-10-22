<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource;

class OcupacoesModal extends ImoveisModal
{
    protected function resource(): string
    {
        return CedidoResource::class;
    }

    protected function recordLabel(): string
    {
        return 'ocupação';
    }

    protected function foreignKey(): string
    {
        return 'id_imovel';
    }
}
