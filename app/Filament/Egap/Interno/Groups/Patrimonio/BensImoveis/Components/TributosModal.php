<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource;

class TributosModal extends ImoveisModal
{
    protected function resource(): string
    {
        return TributoResource::class;
    }

    protected function recordLabel(): string
    {
        return 'tributo';
    }
}
