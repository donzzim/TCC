<?php

namespace App\Filament\Resources\Escola\CursoFrequenciaResource\Pages;

use App\Filament\Resources\Escola\CursoFrequenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCursoFrequencia extends EditRecord
{
    protected static string $resource = CursoFrequenciaResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['cursoatividade_id'])) {

            $data['curso_id'] = $data['cursoatividade_id'];
        }

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
