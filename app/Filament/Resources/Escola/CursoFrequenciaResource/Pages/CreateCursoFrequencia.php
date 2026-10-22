<?php

namespace App\Filament\Resources\Escola\CursoFrequenciaResource\Pages;

use App\Filament\Resources\Escola\CursoFrequenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCursoFrequencia extends CreateRecord
{
    protected static string $resource = CursoFrequenciaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set the 'curso_id' based on the 'atividade_id'
        if (isset($data['atividade_id'])) {

            $data['curso_id'] = $data['atividade->id'];
        }

        return $data;
    }
}
