<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotaFiscal extends EditRecord
{
    protected static string $resource = NotaFiscalResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['valor_total'] = NotaFiscalResource::calcularValorTotal($this->data['itens'] ?? []);
        $data['atualizado_por'] = auth()->id();
        $data['date_time'] = now();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
