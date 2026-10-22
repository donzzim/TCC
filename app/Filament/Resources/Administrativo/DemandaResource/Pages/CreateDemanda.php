<?php

namespace App\Filament\Resources\Administrativo\DemandaResource\Pages;

use App\Filament\Resources\Administrativo\DemandaResource;
use App\Models\Administrativo\Agenda;
use App\Services\AgendaService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDemanda extends CreateRecord
{
    protected static string $resource = DemandaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
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
            }
        }


        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->data;
        $record = $this->getRecord();
        if (isset($data['local_id'])) {
            $record->agendas()->create(
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


    // protected function getCreatedNotification(): ?Notification
    // {
    //     return Notification::make()
    //         ->success()
    //         ->title('Demanda')
    //         ->body('Demanda registrada com sucesso.')
    //         ->sendToDatabase(Auth::user());
    // }
}
