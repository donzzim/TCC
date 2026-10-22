<?php

namespace App\Filament\Concerns;

use App\Models\Administrativo\Agenda;
use App\Services\AgendaService;
use Filament\Notifications\Notification;

trait InteractsWithAgendaValidations
{
    public function hasAgendaConflito(): bool
    {
        try {
            $data = $this->data ?? [];
            $local_id = $data['local_id'];
            $inicio = $data['data_prevista'];
            $termino = $data['data_termino'];


            if ($local_id && $inicio && $termino) {

                if (AgendaService::verificaConflito($local_id, $termino, $inicio)) {
                    Notification::make()
                        ->title('Conflito de Agenda')
                        ->body('Já existe um agendamento neste local entre ' . $inicio . ' e ' . $termino)
                        ->danger()
                        ->send();
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao verificar conflito de agenda')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return false;
        }
    }
}
