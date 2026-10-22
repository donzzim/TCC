<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaFiscal extends CreateRecord
{
    protected static string $resource = NotaFiscalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['valor_total'] = NotaFiscalResource::calcularValorTotal($this->data['itens'] ?? []);
        $data['atualizado_por'] = auth()->id();
        $data['date_time'] = now();

        return $data;
    }
}
