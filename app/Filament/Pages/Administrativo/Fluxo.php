<?php

namespace App\Filament\Pages\Administrativo;

use UnitEnum;
use BackedEnum;
use App\Filament\Clusters\Administrativo\FluxoCluster;
use App\Filament\Resources\Administrativo\CardResource;
use App\Filament\Resources\Administrativo\DemandaResource;
use App\Filament\Resources\Administrativo\ListResource;
use App\Filament\Resources\Administrativo\TaskResource;
use App\Models\Administrativo\Cards;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\Lists;
use App\Models\Administrativo\Tasks;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Database\Eloquent\Collection;

class Fluxo extends Page implements HasInfolists
{
    use InteractsWithInfolists, HasPageShield;
    // protected static string | UnitEnum | null $navigationGroup = 'Fluxo de Trabalho';
    protected static ?string $cluster = FluxoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Fluxo';
    protected static ?string $modelLabel = 'Fluxo';
    protected static ?string $puralModelLabel = 'Fluxo';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.fluxo';
    public $lists;

    public function mount(): void
    {
        $this->loadTasks();
    }
    public function loadTasks()
    {
        $this->lists = Lists::all();
    }
    public function createListAction(): Action
    {
        return Action::make('createList')
            ->label('Nova Lista')
            ->icon('heroicon-o-plus')
            ->form(
                ListResource::getFormfields()
            )
            ->mountUsing(function (Schema $form, array $arguments) {
                $form->fill([]);
            })
            ->action(function (array $data) {
                Lists::create($data);
                $this->loadTasks();
                Notification::make()->title('List criada com sucesso!')->success()->send();
            });
    }

    public function editListAction(): Action
    {
        return Action::make('editList')
            ->label('Editar Lista')
            ->icon('heroicon-o-plus')
            ->form(
                ListResource::getFormfields()
            )
            ->mountUsing(function (Form $form, array $arguments) {
                $row = Lists::find($arguments['id']);
                $form->fill($row->toArray());
            })
            ->action(function (array $data, Lists $record): void {
                $record->update($data);
            })
            ->successNotificationTitle('Lista atualizada!');
    }
    public function createCardAction(): Action
    {
        return Action::make('createCard')
            ->label('Nova Card')
            ->icon('heroicon-o-plus')
            ->form(
                CardResource::getFormfields()
            )
            ->mountUsing(function (Form $form, array $arguments) {
                $form->fill($arguments);
            })
            ->action(function (array $data) {
                Cards::create($data);
                $this->loadTasks();
                Notification::make()->title('Card criada com sucesso!')->success()->send();
            });
    }

    public function editCardAction(): Action
    {
        return Action::make('editCard')
            ->label('Editar Card')
            ->icon('heroicon-o-plus')
            ->form(
                CardResource::getFormfields()
            )
            ->mountUsing(function (Form $form, array $arguments) {
                $row = Cards::find($arguments['id']);
                $form->fill($row->toArray());
            })
            ->action(function (array $data, Cards $record): void {
                $record->update($data);
            })
            ->successNotificationTitle('Card atualizada!');
    }
    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->label('Nova Task')
            ->icon('heroicon-o-plus')
            ->form(
                TaskResource::getFormfields()
            )
            ->mountUsing(function (Form $form, array $arguments) {
                $form->fill($arguments);
            })
            ->action(function (array $data) {
                Tasks::create($data);
                $this->loadTasks();
                Notification::make()->title('Task criada com sucesso!')->success()->send();
            });
    }

    public function editTaskAction(): Action
    {
        return Action::make('editTask')
            ->label('Editar Task')
            ->icon('heroicon-o-plus')
            ->form(
                TaskResource::getFormfields()
            )
            ->mountUsing(function (Form $form, array $arguments) {
                $row = Tasks::find($arguments['id']);
                $form->fill($row->toArray());
            })
            ->action(function (array $data, Tasks $record): void {
                $record->update($data);
            })
            ->successNotificationTitle('Task atualizada!');
    }
}
