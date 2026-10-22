<?php

namespace App\Filament\Resources\Escola\CursosResource\Pages;

use App\Filament\Resources\Escola\CursosResource;
use App\Models\Administrativo\Agenda;
use App\Services\AgendaService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCursos extends CreateRecord
{
    protected static string $resource = CursosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['termino']) && isset($data['inicio'])) {

            if (AgendaService::VerificaConflito(
                $data['tipolocal_id'],
                $data['termino'],
                $data['inicio']
            )) {
                $this->halt();
            }
        }
        // dd($data);



        return $data;
    }

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }
}
