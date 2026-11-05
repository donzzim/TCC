<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Fontes\JobsFalhos;
use App\Services\QueueService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class JobsFalhosTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        $jobs = JobsFalhos::recentes(50);

        return $table
            ->heading('Jobs falhos (50 mais recentes)')
            ->records(fn (): array => array_map(fn (array $j): array => ['__key' => $j['uuid'], ...$j], $jobs ?? []))
            ->columns([
                TextColumn::make('falhou_em')->label('Falhou em')->dateTime('d/m/Y H:i'),
                TextColumn::make('job')->label('Job')->fontFamily('mono')->size('sm')->description(fn (array $record) => 'fila '.$record['fila']),
                TextColumn::make('excecao')->label('Erro')->wrap()->size('sm'),
            ])
            ->headerActions([
                Action::make('iniciarWorker')
                    ->label('Iniciar worker')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn () => $this->avisar(app(QueueService::class)->start())),
                Action::make('pararWorker')
                    ->label('Parar worker')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->avisar(app(QueueService::class)->stop())),
            ])
            ->recordActions([
                Action::make('tentarDeNovo')
                    ->label('Tentar de novo')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription('O job volta para a fila e será executado pelo worker.')
                    ->action(function (array $record): void {
                        Artisan::call('queue:retry', ['id' => [$record['uuid']]]);
                        Notification::make()->title('Job reenviado para a fila')->success()->send();
                    }),
                Action::make('descartar')
                    ->label('Descartar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('O registro do job falho será apagado. Isso não pode ser desfeito.')
                    ->action(function (array $record): void {
                        Artisan::call('queue:forget', ['id' => $record['uuid']]);
                        Notification::make()->title('Job falho descartado')->success()->send();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading($jobs === null ? 'Lista de jobs falhos indisponível' : 'Nenhum job falho');
    }

    private function avisar(array $resultado): void
    {
        Notification::make()
            ->title($resultado['message'] ?? ($resultado['success'] ?? false ? 'Feito' : 'Não foi possível'))
            ->status(($resultado['success'] ?? false) ? 'success' : 'danger')
            ->send();
    }
}
