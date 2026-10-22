<?php

namespace App\Filament\Resources\Administrativo\DemandaResource\Pages;

use App\Filament\Resources\Administrativo\DemandaResource;
use App\Models\Administrativo\Agenda;
use App\Services\AgendaService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDemanda extends EditRecord
{
    protected static string $resource = DemandaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['local_id'])) {

            if (AgendaService::VerificaConflito(
                $data['local_id'],
                $data['data_termino'],
                $data['data_prevista'],
                [],
                [$data['id']] ?? []
            )) {
                $this->halt();
            } else {
                $this->record->agendas()->updateOrCreate(
                    [
                        'agendaable_id' => $this->record->id,
                        'agendaable_type' => get_class($this->record),
                    ],
                    [
                        'local_id' => $data['local_id'],
                        'inicio' => $data['data_prevista'],
                        'termino' => $data['data_termino'],
                        'titulo' => 'Demanda',
                        'descricao' => $data['titulo'],
                    ]
                );
            }
        }


        return $data;
    }



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
    public function getContentTabIcon(): ?string
    {
        return 'heroicon-m-pencil-square';
    }
}
