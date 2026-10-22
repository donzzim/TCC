<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\BemIntangivelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\BemIntangivelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBemIntangivel extends EditRecord
{
    protected static string $resource = BemIntangivelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
