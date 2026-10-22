<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Administrativo\CronogramaTasksWidget;
use BackedEnum;

class CronogramaTarefas extends \Filament\Pages\Dashboard
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Tarefas';
    protected static ?string $title = 'Tarefas';
    protected static string $routePath = 'tarefas';
    protected static ?string $slug = 'tarefas';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            CronogramaTasksWidget::class,
        ];
    }
}
