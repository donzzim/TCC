<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Pages;

use App\Filament\Egap\Externo\Groups\Agendamento\Components\MeusAgendamentosTable;

class AgendamentoUser extends AgendamentoPage
{
    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $slug = 'agendamento/meus-agendamentos';

    protected static ?string $navigationLabel = 'Meus Agendamentos';

    protected static ?string $title = 'Minhas Solicitações';

    protected function limitarAoUsuarioAtual(): bool
    {
        return true;
    }

    public function component(): string
    {
        return MeusAgendamentosTable::class;
    }
}
