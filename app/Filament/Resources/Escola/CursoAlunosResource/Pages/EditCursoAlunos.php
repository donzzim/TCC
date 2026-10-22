<?php

namespace App\Filament\Resources\Escola\CursoAlunosResource\Pages;

use App\Filament\Resources\Escola\CursoAlunosResource;
use App\Services\SaveService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCursoAlunos extends EditRecord
{
    public array $formOriginal = [];
    protected static string $resource = CursoAlunosResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {

        return SaveService::mergeData($this->getRecord(), $data, $this->formOriginal);
    }

    protected function afterFill(): void
    {
        $this->formOriginal = $this->data;
        // dd($this->formOriginal);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
