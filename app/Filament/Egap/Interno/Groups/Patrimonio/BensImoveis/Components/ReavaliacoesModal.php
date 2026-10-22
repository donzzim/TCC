<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ReavaliacaoResource;

class ReavaliacoesModal extends ImoveisModal
{
    protected function resource(): string
    {
        return ReavaliacaoResource::class;
    }

    protected function recordLabel(): string
    {
        return 'reavaliação';
    }
}
