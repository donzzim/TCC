<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\CentroCustoResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\CentroCustoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCentroCusto extends EditRecord
{
    protected static string $resource = CentroCustoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
