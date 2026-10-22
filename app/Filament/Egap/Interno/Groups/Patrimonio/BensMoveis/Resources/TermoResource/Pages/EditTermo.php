<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTermo extends EditRecord
{
    protected static string $resource = TermoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
