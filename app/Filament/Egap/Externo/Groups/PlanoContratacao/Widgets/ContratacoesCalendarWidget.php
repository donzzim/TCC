<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoContratacaoContexto;
use App\Models\egap\PlanoContratacao\BemServico;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calendário de Contratações (legado: `pla_calendario.php` /
 * `pla_calendario.api.php`), no mesmo padrão de grade mensal usado no
 * Agendamento de Veículos.
 *
 * Dois tipos de evento:
 * - "Previsto" (azul): item Concluído posicionado na `data_estimada`; vermelho
 *   se a data já passou.
 * - "Validado" (verde): item Validado posicionado na `data_executada`; texto
 *   vermelho quando executado depois da data estimada (atraso).
 *
 * Restringe aos setores do usuário atual ({@see PlanoContratacaoContexto}).
 */
class ContratacoesCalendarWidget extends Widget implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'egap.filament.widgets.plano-contratacao-calendar-widget';

    public ?Carbon $currentMonth = null;

    public int $daysInMonth = 30;

    public string $monthName = '';

    public int $firstDayOfWeek = 0;

    /** @var array<int, array<string, mixed>> */
    public array $events = [];

    public function getColumnSpan(): string|int|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $this->currentMonth = now()->startOfMonth();
        $this->loadEvents();
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->loadEvents();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        $inicio = $this->currentMonth->copy()->startOfMonth();
        $fim = $this->currentMonth->copy()->endOfMonth();

        $this->daysInMonth = $this->currentMonth->daysInMonth;
        $this->monthName = $this->currentMonth->translatedFormat('F Y');
        $this->firstDayOfWeek = $this->currentMonth->copy()->startOfMonth()->dayOfWeek;

        $setores = PlanoContratacaoContexto::setoresElegiveis();

        if ($setores === []) {
            $this->events = [];

            return;
        }

        $base = fn (): Builder => BemServico::query()
            ->with(['plano.coordenadoriaRef'])
            ->whereHas('plano', fn (Builder $query) => $query->whereIn('cd_coordenadoria', $setores));

        $previstos = (clone $base())
            ->where('id_etapa', SituacaoPlano::Concluido->value)
            ->whereBetween('data_estimada', [$inicio->toDateString(), $fim->toDateString()])
            ->get()
            ->map(fn (BemServico $item): array => $this->evento($item, $item->data_estimada, 'previsto'));

        $validados = (clone $base())
            ->where('id_etapa', SituacaoPlano::Validado->value)
            ->whereBetween('data_executada', [$inicio->toDateString(), $fim->toDateString()])
            ->get()
            ->map(fn (BemServico $item): array => $this->evento($item, $item->data_executada, 'validado'));

        $this->events = $previstos->concat($validados)->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function evento(BemServico $item, ?Carbon $data, string $tipo): array
    {
        $atrasado = $tipo === 'previsto'
            ? ($data !== null && $data->isPast())
            : ($item->data_estimada !== null && $data !== null && $item->data_estimada->lt($data));

        return [
            'id' => $item->id,
            'start' => optional($data)->toDateString(),
            'end' => optional($data)->toDateString(),
            'titulo' => $item->nome,
            'setor' => $item->plano?->coordenadoriaRef?->unidadeorganizacional ?? '—',
            'tipo' => $tipo,
            'cor' => $tipo === 'previsto' ? ($atrasado ? 'danger' : 'info') : 'success',
            'texto_atrasado' => $atrasado,
        ];
    }

    public function verEventoAction(): Action
    {
        return Action::make('verEvento')
            ->label('Detalhes da contratação')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalHeading(fn (array $arguments): string => 'Contratação #'.($arguments['event_id'] ?? ''))
            ->modalContent(function (array $arguments): View {
                $item = BemServico::query()
                    ->with(['plano.coordenadoriaRef'])
                    ->find($arguments['event_id'] ?? null);

                return view('egap.livewire.externo.plano-contratacao.evento-calendario', ['item' => $item]);
            });
    }

    public function render(): View
    {
        return view($this->view);
    }
}
