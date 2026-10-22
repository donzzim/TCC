<?php

namespace App\Filament\Pages\Planejamento;

use UnitEnum;
use BackedEnum;
use App\Filament\Resources\Planejamento\IniciativasEstrategicasResource;
use App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource;
use App\Models\Planejamento\IniciativasEstrategicas;
use App\Models\Planejamento\ObjetivosEstrategicos;
use App\Models\Planejamento\PlanejamentoEstrategico;
use App\Models\Traits\HasSetorScope;
use Filament\Forms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MapaEstrategico extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?PlanejamentoEstrategico $planejamento = null;
    public $perspectivas = [];

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map';
    // protected static ?string $recordTitleAttribute = 'tema';
    protected static ?string $modelLabel = 'Mapa Estratégico ';
    protected static ?string $puralModelLabel = 'Mapa Estratégico ';
    protected static ?string $navigationLabel = 'Mapa Estratégico ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Estratégico';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.planejamento.mapa-estrategico';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('createObjetivo')
                ->label('Novo Objetivo')
                ->model(ObjetivosEstrategicos::class)
                ->form(
                    ObjetivosEstrategicosResource::getFormfields()
                )
                ->successNotificationTitle('Objetivo criado com sucesso!')
                ->slideOver(),

            CreateAction::make('createIniciativa')
                ->label('Nova Iniciativa')
                ->model(IniciativasEstrategicas::class)
                ->form(
                    IniciativasEstrategicasResource::getFormfields()
                )
                ->successNotificationTitle('Iniciativa criada com sucesso!')
                ->slideOver(),
        ];
    }
    public function editObjetivo(): Action
    {
        return Action::make('editObjetivo')
            ->label('Editar Objetivo')
            ->mountUsing(function (Form $form, array $arguments) {
                $row = ObjetivosEstrategicos::find($arguments['id']);
                if (!$row) {
                    Notification::make()->title('Objetivo não encontrado!')->danger()->send();
                    $this->halt();
                }
                $form->fill($row->toArray());
            })
            ->form(
                ObjetivosEstrategicosResource::getFormfields()
            )
            ->action(function (array $data, ObjetivosEstrategicos $record): void {
                $record->update($data);
            })
            ->successNotificationTitle('Objetivo atualizado!');
    }
    public function editIniciativa(): Action
    {
        return Action::make('editIniciativa')
            ->label('Editar Iniciativa')
            ->mountUsing(function (Form $form, array $arguments) {
                $row = IniciativasEstrategicas::find($arguments['id']);
                if (!$row) {
                    Notification::make()->title('Objetivo não encontrado!')->danger()->send();
                    $this->halt();
                }
                $form->fill($row->toArray());
            })
            ->form(
                IniciativasEstrategicasResource::getFormfields()
            )
            ->action(function (array $data, IniciativasEstrategicas $record): void {
                $record->update($data);
            })
            ->successNotificationTitle('Iniciativa atualizada!');
    }

    public function mount()
    {
        $this->planejamento = PlanejamentoEstrategico::latest()->first();

        if ($this->planejamento) {
            $objetivos = ObjetivosEstrategicos::where('planejamento_id', $this->planejamento->id)
                ->with(['perspectiva', 'macrodesafio', 'iniciativas'])
                ->get();
            $this->perspectivas = $objetivos->mapToGroups(function ($objetivo) {
                return [$objetivo->perspectiva?->perspectiva => $objetivo];
            });
        }
    }
}
