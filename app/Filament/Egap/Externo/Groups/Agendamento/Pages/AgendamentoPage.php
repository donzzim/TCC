<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\Agendamento\Widgets\AgendamentosSetorCalendarWidget;
use App\Filament\Egap\Externo\Groups\Agendamento\Widgets\MeusAgendamentosCalendarWidget;
use Filament\Pages\Page;

/**
 * Base das páginas de calendário/listagem de agendamento de veículos do
 * Ambiente Externo, compartilhada entre {@see AgendamentoUser} (só do
 * usuário atual) e {@see AgendamentoGerais} (todo o Setor atual).
 *
 * Usuário e setor vêm da sessão ({@see \App\Filament\Egap\Auth\EgapSession}):
 * o usuário sai do CPF de quem está logado e o setor da lotação, trocável pelo
 * seletor do topo. Antes disso havia dois modais encadeados que pediam as duas
 * coisas à mão.
 *
 * A única diferença real entre as duas páginas é {@see limitarAoUsuarioAtual()},
 * que escolhe a tabela e o widget de calendário corretos — mesmo padrão de
 * {@see \App\Filament\Egap\Externo\Groups\Agendamento\Components\AgendamentoTable}.
 */
abstract class AgendamentoPage extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Agendamento de Veículos';

    protected string $view = 'egap.filament.pages.externo.agentamento.agendamentos';

    abstract protected function limitarAoUsuarioAtual(): bool;

    abstract public function component(): string;

    protected function getHeaderWidgets(): array
    {
        return [
            $this->limitarAoUsuarioAtual()
                ? MeusAgendamentosCalendarWidget::class
                : AgendamentosSetorCalendarWidget::class,
        ];
    }
}
