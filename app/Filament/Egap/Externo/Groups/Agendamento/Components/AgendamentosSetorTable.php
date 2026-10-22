<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Components;

/**
 * Lista todas as solicitações de agendamento de veículo do Setor atualmente
 * identificado (ver {@see \App\Filament\Egap\Support\SetorSelecionado}),
 * independente de qual usuário solicitou.
 */
class AgendamentosSetorTable extends AgendamentoTable
{
    protected function limitarAoUsuarioAtual(): bool
    {
        return false;
    }
}
