<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Widgets;

use App\Filament\Egap\Externo\Groups\Agendamento\Concerns\FormataSolicitacao;
use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\Agendamento\Solicitacao;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calendário mensal das solicitações de agendamento de veículo (legado:
 * calendario_agendamento.php + api/calendario.api.php), no mesmo padrão de
 * grade/navegação de mês usado em outras áreas do EMES para calendários de
 * evento — adaptado aqui para o domínio de {@see Solicitacao}, sem a parte de
 * gestão de tarefas (Demanda/Processo/RegistroFuncional), que não existe
 * nesse contexto.
 *
 * Compartilhada entre {@see MeusAgendamentosCalendarWidget} (só do usuário
 * atual) e {@see AgendamentosSetorCalendarWidget} (todo o Setor atual) — a
 * única diferença é {@see limitarAoUsuarioAtual()}, igual à
 * {@see \App\Filament\Egap\Externo\Groups\Agendamento\Components\AgendamentoTable}.
 */
abstract class AgendamentoCalendarWidget extends Widget implements HasActions
{
    use FormataSolicitacao;
    use InteractsWithActions;

    protected string $view = 'egap.filament.widgets.agendamento-calendar-widget';

    public $currentMonth;
    public $daysInMonth;
    public $monthName;
    public $firstDayOfWeek;
    public $events = [];
    public string $viewMode = 'grid';

    abstract protected function limitarAoUsuarioAtual(): bool;

    public function getColumnSpan(): string|int
    {
        return 'full';
    }

    public function mount(): void
    {
        $this->currentMonth = now()->startOfMonth();

        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        $start = $this->currentMonth->copy()->startOfMonth()->toDateString();
        $end = $this->currentMonth->copy()->endOfMonth()->toDateString();

        $this->daysInMonth = $this->currentMonth->daysInMonth;
        $this->monthName = $this->currentMonth->translatedFormat('F Y');
        $this->firstDayOfWeek = $this->currentMonth->startOfMonth()->dayOfWeek;

        $this->events = $this->getQuery()
            ->where(fn(Builder $query) => $query
                ->whereBetween('data_inicio', [$start, $end])
                ->orWhereBetween('data_termino', [$start, $end]))
            ->orderBy('data_inicio')
            ->get()
            ->map(function (Solicitacao $solicitacao): array {
                $inicio = Carbon::parse($solicitacao->data_inicio);
                $termino = filled($solicitacao->data_termino) ? Carbon::parse($solicitacao->data_termino) : $inicio;

                return [
                    'id' => $solicitacao->id,
                    'start' => $inicio->toDateString(),
                    'end' => $termino->toDateString(),
                    'hora_inicio' => $solicitacao->hora_inicio,
                    'hora_termino' => $solicitacao->hora_termino,
                    'trajeto' => $this->rotaTexto($solicitacao),
                    'solicitante' => $solicitacao->idSolicitanteRef?->name ?? 'Não informado',
                    'situacao' => (int) $solicitacao->id_situacao,
                    'situacao_descricao' => $solicitacao->idSituacaoRef?->Descricao ?? '-',
                    'cor' => $this->statusColor((int) $solicitacao->id_situacao),
                ];
            })
            ->toArray();
    }

    protected function getQuery(): Builder
    {
        $setorId = SetorSelecionado::resolverAtual();
        $usuarioId = $this->limitarAoUsuarioAtual() ? UsuarioSelecionado::resolverAtual() : null;

        return Solicitacao::query()
            ->where('tipo', Solicitacao::TIPO_VEICULOS)
            ->when(
                blank($setorId) || ($this->limitarAoUsuarioAtual() && blank($usuarioId)),
                fn(Builder $query) => $query->whereKey(-1),
                fn(Builder $query) => $query
                    ->where('setor_solicitante', $setorId)
                    ->when($usuarioId, fn(Builder $query) => $query->where('id_solicitante', $usuarioId))
            )
            ->with(['idSituacaoRef', 'idSolicitanteRef']);
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->loadEvents();
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->loadEvents();
    }

    public function verAgendamentoAction(): Action
    {
        return Action::make('verAgendamento')
            ->label('Ver Agendamento')
            ->modalHeading(function (array $arguments): string {
                $id = $arguments['event_id'] ?? null;

                return $id ? "Agendamento da solicitação nº {$id}" : 'Agendamento';
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(function (array $arguments): View {
                $solicitacao = Solicitacao::query()->find($arguments['event_id'] ?? null);

                return view('egap.livewire.externo.detalhes-agendamento', [
                    'recursos' => $solicitacao?->recursosRef()->with(['condutorRef.idPessoaRef', 'veiculoRef'])->get() ?? collect(),
                ]);
            });
    }
}
