<?php

namespace App\Filament\Widgets\Administrativo;

class CronogramaTasksWidget extends EventoCalendarWidget
{
    public $viewMode = 'tasks';
    public bool $exibirModoCalendario = false;
    public bool $exibirModoTarefas = true;
}
