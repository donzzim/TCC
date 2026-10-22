<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Widgets;

/**
 * Calendário de todas as solicitações de agendamento de veículo do Setor
 * atualmente identificado, independente de qual usuário solicitou.
 */
class AgendamentosSetorCalendarWidget extends AgendamentoCalendarWidget
{
    protected function limitarAoUsuarioAtual(): bool
    {
        return false;
    }
}
