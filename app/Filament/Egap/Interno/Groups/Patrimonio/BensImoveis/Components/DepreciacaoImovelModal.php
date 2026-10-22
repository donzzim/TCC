<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DepreciacaoImovelModal extends ImoveisModal
{
    protected function resource(): string
    {
        return DepreciacaoResource::class;
    }

    protected function recordLabel(): string
    {
        return 'depreciação';
    }

    protected function configureModalTable(Table $table): Table
    {
        return $table
            ->filters([])
            ->emptyStateHeading('Nenhum registro encontrado')
            ->emptyStateDescription('')
            ->emptyStateIcon(Heroicon::OutlinedXMark);
    }
}
