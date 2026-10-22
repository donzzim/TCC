<?php

namespace App\Filament\Widgets\Administrativo;

use App\Models\Administrativo\Agenda;
use App\Models\Escola\CursoAtividades;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Escola\Cursos;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
// use Guava\Calendar\ValueObjects\CalendarEvent;
// use Guava\Calendar\Widgets\CalendarWidget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class AgendaWidget // extends CalendarWidget
{
    use InteractsWithPageFilters, HasWidgetShield;
    // protected string $view = 'filament.widgets.agenda-widget';
    // protected string $calendarView = 'resourceTimeGridWeek';
    protected ?string $locale = 'pt';
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '100s';
    protected bool $eventClickEnabled = true;
    // protected ?string $defaultEventClickAction = 'edit';

    #[On('toggleAgenda')]
    public function refresh()
    {
        // $this->refreshRecords();
    }
    public function getEvents(array $fetchInfo = []): Collection|array
    {
        $inicio = date('Y-m-d') . ' 00:00:01';
        $termino = date('Y-m-d') . ' 23:59:59';
        if (isset($this->filters['inicio']))
            $inicio = $this->filters['inicio'] . ' 00:00:01';
        if (isset($this->filters['termino']))
            $termino = $this->filters['termino'] . ' 23:59:59';

        $eventos = [];

        // $agenda = Agenda::where('inicio', '>=', $inicio)
        //     ->where('termino', '<=', $termino)
        //     ->get();
        // foreach ($agenda as $item) {
        //     if ($this->filters['feriados']) {
        //         $eventos[] = CalendarEvent::make()
        //             ->title("{$item->titulo} {$item->descricao}")
        //             ->start($item->inicio)
        //             ->end($item->termino)
        //             ->allDay(($item->dia_todo ? true : false))
        //             ->textColor($item->cor_texto ?? '#FFFFFF')
        //             ->backgroundColor($item->cor_fundo ?? "rgba(0.5, 0.4, 0.2, 0.4)")
        //             ->url("/admin/agendas/{$item->id}/edit", "_blank")
        //             ->model(Agenda::class)
        //             ->key($item->id)
        //             ->action('view');
        //     }
        // }

        // if (isset($this->filters['registros'])) {
        //     $registro = RegistroFuncional::where('inicio', '>=', $inicio)
        //         ->where('termino', '<=', $termino)
        //         ->get();
        //     foreach ($registro as $item) {
        //         $eventos[] = CalendarEvent::make()
        //             ->title("{$item->tipo->descricao} {$item->pessoa->name}")
        //             ->start($item->inicio)
        //             ->end(($item->termino ? $item->termino : $item->inicio))
        //             ->allDay(false)
        //             ->textColor('#ffffff')
        //             ->backgroundColor('#2545ba')
        //             ->url("/admin/registro-funcionals/{$item->id}/edit", "_blank")
        //             ->model(RegistroFuncional::class)
        //             ->key($item->id)
        //             ->action('view');
        //     }
        // }

        // if (isset($this->filters['cursos'])) {
        //     $cursos = CursoAtividades::where('inicio', '>=', $inicio)
        //         ->where('termino', '<=', $termino)

        //         ->get();

        //     foreach ($cursos as $item) {
        //         if (! $item->curso) {
        //             continue;
        //         }
        //         $dtini = Carbon::createFromFormat('Y-m-d H:i', $item->inicio->format('Y-m-d H:i'));
        //         $dtter = Carbon::createFromFormat('Y-m-d H:i', $item->termino->format('Y-m-d H:i'));
        //         $hrini = $item->inicio->format('H:i');
        //         $hrter = $item->termino->format('H:i');
        //         $days = (int) $dtini->diffInDays($dtter);

        //         $dt = $dtini->format('Y-m-d');
        //         for ($i = 0; $i < $days; $i++) {
        //             $eventos[] = CalendarEvent::make()
        //                 ->title("{$item->curso->titulo}")
        //                 ->start("{$dt} {$hrini}")
        //                 ->end("{$dt} {$hrter}")
        //                 ->allDay(false)
        //                 ->textColor('#ffffff')
        //                 ->backgroundColor('#28b28e')
        //                 ->url("/admin/curso-atividades/{$item->id}/edit", "_blank")
        //                 ->model(CursoAtividades::class)
        //                 ->key($item->id)
        //                 ->action('view');
        //             $dt = $dtini->addDay()->format('Y-m-d');
        //         }
        //     }
        // }

        return $eventos;
    }

    /*
    public function getEventContent(): null|string|array
    {
        // return a blade view
        // return view('calendar.event');

        // return a HtmlString
        return new HtmlString('<div>My event</div>');
    }
    */

    /*
    public function getResourceLabelContent(): null|string|array
    {
        // return a blade view
        return view('calendar.resource');

        // return a HtmlString
        return new HtmlString('<div>My resource</div>');
    }
    */
    /*
    public function getSchema(?string $model = null): ?array
    {
        // If you only work with one model type, you can ignore the $model parameter and simply return a schema
        return [
            TextInput::make('title')
        ];

        // If you have multiple model types on your calendar, you can return different schemas based on the $model property
        return match($model) {
            Foo::class => [
                TextInput::make('name'),
            ],
            Bar::class => [
                TextInput::make('title'),
                TextArea::make('description'),
            ],
        }
    }
    */
    /*
    public function getOptions(): array
    {
        return [
            'nowIndicator' => true,
            'slotDuration' => '00:15:00'
        ];
    }
    */
    // public function getEventClickContextMenuActions(): array
    // {
    //     return [
    //         $this->viewAction(),
    //         $this->editAction(),
    //         $this->deleteAction(),
    //     ];
    // }
}
