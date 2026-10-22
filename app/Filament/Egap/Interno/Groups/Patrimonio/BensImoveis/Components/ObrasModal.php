<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource;

class ObrasModal extends ImoveisModal
{
    protected function resource(): string
    {
        return ObraResource::class;
    }

    protected function recordLabel(): string
    {
        return 'obra';
    }

    protected function foreignKey(): string
    {
        return 'id_imovel';
    }
}
