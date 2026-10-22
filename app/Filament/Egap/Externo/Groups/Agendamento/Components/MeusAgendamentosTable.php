<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Components;

/**
 * Lista só as solicitações de agendamento de veículo do usuário atualmente
 * identificado (ver {@see \App\Filament\Egap\Support\UsuarioSelecionado}).
 */
class MeusAgendamentosTable extends AgendamentoTable
{
    protected function limitarAoUsuarioAtual(): bool
    {
        return true;
    }
}
