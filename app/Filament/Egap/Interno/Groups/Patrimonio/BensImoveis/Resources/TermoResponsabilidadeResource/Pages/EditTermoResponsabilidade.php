<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TermoResponsabilidadeResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TermoResponsabilidadeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTermoResponsabilidade extends EditRecord
{
    protected static string $resource = TermoResponsabilidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir'),
        ];
    }
}
