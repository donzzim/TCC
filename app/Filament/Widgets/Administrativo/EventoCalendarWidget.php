<?php

namespace App\Filament\Widgets\Administrativo;

use App\Filament\Widgets\Administrativo\Concerns\InteractsWithCronogramaTasks;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarActions;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarData;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarHelpers;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarNavigation;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarPresence;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarRealocacao;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoCalendarSystemEvents;
use App\Filament\Widgets\Administrativo\Concerns\InteractsWithEventoListaEditing;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;


class EventoCalendarWidget extends Widget implements HasSchemas, HasActions
{
    use InteractsWithSchemas;
    use InteractsWithActions;
    use InteractsWithCronogramaTasks;
    use InteractsWithEventoCalendarData;
    use InteractsWithEventoCalendarPresence;
    use InteractsWithEventoCalendarNavigation;
    use InteractsWithEventoCalendarActions;
    use InteractsWithEventoCalendarRealocacao;
    use InteractsWithEventoListaEditing;
    use InteractsWithEventoCalendarHelpers;
    use InteractsWithEventoCalendarSystemEvents;

    protected string $view = 'filament.widgets.evento-calendar-widget';
    protected const EVENTOS_CACHE_TTL_SECONDS = 60;
    protected const PRESENCA_CRONOGRAMA_TTL_SECONDS = 45;
    protected const PRESENCA_CRONOGRAMA_MAX_KEYS = 80;

    protected array $agendaCronogramaCache = [];
    protected array $events = [];
    protected array $calendarEventsByDate = [];
    protected array $listEventDays = [];
    protected array $realocacaoAgendaablesCache = [];
    protected array $realocacaoAgendasCache = [];
    protected array $taskList = [];
    protected bool $eventsLoaded = false;
    protected bool $taskListLoaded = false;

    public $currentMonth;
    public $daysInMonth;
    public $monthName;
    public $firstDayOfWeek;
    public array $eventoCronogramaData = [];
    public ?int $eventoListaAbertoId = null;
    public ?int $eventoListaRealocarAgendaId = null;
    public array $eventoListaForm = [];
    public array $eventoListaLocalOptions = [];
    public $myTasks = [];
    public $viewMode = 'grid';
    public bool $exibirModoCalendario = true;
    public bool $exibirModoTarefas = false;
    public ?array $taskFilters = [];
    public string $taskViewType = 'grid';

    // Propriedades para Gestáo de Tarefas
    public $isManager = true; // Controle de visualização
    public $filterContext = 'all'; // all, administrative, pedagogic, process

    public function getColumnSpan(): string | int
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        $this->setCalendarMetadata();

        return match ($this->viewMode) {
            'grid' => [
                'calendarEventsByDate' => $this->getCalendarEventsByDate(),
                'listEventDays' => [],
                'taskList' => [],
            ],
            'list' => [
                'calendarEventsByDate' => [],
                'listEventDays' => $this->getListEventDays(),
                'taskList' => [],
            ],
            'tasks' => [
                'calendarEventsByDate' => [],
                'listEventDays' => [],
                'taskList' => $this->getTaskListForRender(),
            ],
            default => [
                'calendarEventsByDate' => [],
                'listEventDays' => [],
                'taskList' => [],
            ],
        };
    }

    public function mount(): void
    {
        $this->currentMonth = now()->startOfMonth();
        $this->viewMode = $this->normalizarViewMode($this->viewMode);

        // Verifica se o usuário ? gestor (Regra: Super Admin ou possui permissóo especifica)
        $user = Auth::user();
        $this->isManager = true; //$user->hasRole('super_admin') || $user->can('manage_tasks');

        $this->taskFiltersForm->fill([
            'status_id' => null,
            'user_id' => null,
            'data_inicio' => null,
            'data_fim' => null,
            'ordenacao' => 'created_at_desc',
        ]);

        if ($this->exibirModoCalendario) {
            $this->loadEvents();
        }

        if ($this->viewMode === 'tasks') {
            $this->loadTasks();
        }
    }

}
