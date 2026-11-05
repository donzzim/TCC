<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Fontes\TarefasAgendadas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgendadorTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Tarefas agendadas')
            ->description('Dependem do "php artisan schedule:run" rodando a cada minuto (Agendador de Tarefas do Windows).')
            ->records(fn (): array => array_map(
                fn (array $t): array => ['__key' => $t['tarefa'], ...$t],
                TarefasAgendadas::listar($this->repositorio()->batimentos())
            ))
            ->columns([
                TextColumn::make('tarefa')->label('Tarefa')->fontFamily('mono')->size('sm'),
                TextColumn::make('expressao')->label('Quando (cron)')->fontFamily('mono')->size('xs'),
                TextColumn::make('ultima')->label('Última execução')
                    ->formatStateUsing(fn ($state) => $state ? now()->setTimestamp($state)->format('d/m H:i:s') : '—')
                    ->placeholder('—'),
                TextColumn::make('proxima')->label('Próxima')->placeholder('—'),
                TextColumn::make('situacao')->label('Situação')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'atrasada' => 'warning',
                        'falhou' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'ok' => 'heroicon-m-check-circle',
                        'atrasada' => 'heroicon-m-clock',
                        'falhou' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->tooltip(fn (array $record) => $record['mensagem']),
            ])
            ->paginated(false);
    }
}
