<?php

namespace App\Filament\Widgets\Administrativo\Concerns;

use Filament\Schemas\Schema;
use App\Models\Administrativo\Agenda;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

trait InteractsWithEventoCalendarSystemEvents
{
    public function editEventAction(): Action
    {
        return Action::make('editEvent')
            ->label('Editar Evento')
            ->icon('heroicon-o-pencil')
            ->schema($this->getEventFormSchema())
            ->mountUsing(function (Schema $form, array $arguments = []) {
                $eventId = $arguments['event_id'] ?? null;
                if (!$eventId) return;

                $evento = Agenda::find($eventId);
                if (!$evento) return;

                if ($evento->agendaable_type) {
                    Notification::make()->title('Eventos de sistema não podem ser editados aqui.')->warning()->send();
                    return;
                }

                $form->fill([
                    'titulo' => $evento->titulo,
                    'inicio' => $evento->inicio,
                    'termino' => $evento->termino,
                    'tipo' => $evento->tipo,
                ]);
            })
            ->action(function (array $data, array $arguments = []) {
                $eventId = $arguments['event_id'] ?? null;
                if (!$eventId) return;

                $evento = Agenda::find($eventId);
                if (!$evento) return;

                $evento->update($data);
                $this->loadEvents(true);
                Notification::make()->title('Evento atualizado!')->success()->send();
            });
    }

    // ... createEventAction e getEventFormSchema permanecem iguais
    public function createEventAction(): Action
    {
        return Action::make('createEvent')
            ->label('Novo Evento')
            ->schema($this->getEventFormSchema())
            ->action(function (array $data) {
                Agenda::create($data);
                $this->loadEvents(true);
                Notification::make()->title('Evento criado!')->success()->send();
            });
    }

    protected function getEventFormSchema(): array
    {
        return [
            TextInput::make('titulo')->required()->label('Título'),
            DateTimePicker::make('inicio')->required()->label('Início'),
            DateTimePicker::make('termino')->required()->label('Término'),
            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'reunião' => 'Reunião',
                    'feriado' => 'Feriado',
                    'aniversário' => 'Aniversário',
                ])
                ->required(),
        ];
    }
}
