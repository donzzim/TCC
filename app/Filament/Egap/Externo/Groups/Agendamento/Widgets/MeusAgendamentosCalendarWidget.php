<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Widgets;

/**
 * Calendário só das solicitações de agendamento de veículo do usuário
 * atualmente identificado.
 */
class MeusAgendamentosCalendarWidget extends AgendamentoCalendarWidget
{
    protected function limitarAoUsuarioAtual(): bool
    {
        return true;
    }
}
