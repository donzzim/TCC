<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Pages;

use App\Filament\Egap\Externo\Groups\Agendamento\Components\AgendamentosSetorTable;
use Filament\Support\Icons\Heroicon;

class AgendamentoGerais extends AgendamentoPage
{
    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $slug = 'agendamento/agendamentos-gerais';

    protected static ?string $navigationLabel = 'Agendamentos Gerais';

    protected static ?string $title = 'Calendário de Agendamentos';

    protected function limitarAoUsuarioAtual(): bool
    {
        return false;
    }

    public function component(): string
    {
        return AgendamentosSetorTable::class;
    }
}
