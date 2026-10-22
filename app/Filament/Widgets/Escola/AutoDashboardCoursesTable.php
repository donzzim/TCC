<?php

namespace App\Filament\Widgets\Escola;

use App\Filament\Resources\Escola\CursosResource;
use App\Models\Escola\Cursos;
use App\Services\Auto\AutoDashboardService;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AutoDashboardCoursesTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;
    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $service = app(AutoDashboardService::class);

        return $table
            ->heading('Cursos em acompanhamento')
            ->description('Visão operacional das automações e das próximas ações.')
            ->poll('60s')
            ->striped()
            ->query($this->filteredQuery(Cursos::query()->where('auto', true)))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->state(fn(Cursos $record): string => $service->courseSnapshot($record)['status_label'])
                    ->badge()
                    ->color(fn(Cursos $record): string => $this->statusColor(
                        $service->courseSnapshot($record)['status']
                    )),
                Tables\Columns\TextColumn::make('pending_count')
                    ->label('Pendências')
                    ->state(fn(Cursos $record): int => $service->courseSnapshot($record)['pending_count'])
                    ->numeric()
                    ->badge()
                    ->color(fn(string|int $state): string => ((int) $state > 0) ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('blocking_count')
                    ->label('Bloqueantes')
                    ->state(fn(Cursos $record): int => $service->courseSnapshot($record)['blocking_count'])
                    ->numeric()
                    ->badge()
                    ->color(fn(string|int $state): string => ((int) $state > 0) ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('next_step')
                    ->label('Próxima etapa')
                    ->state(fn(Cursos $record): ?string => $service->courseSnapshot($record)['next_step'])
                    ->placeholder('Nenhuma')
                    ->wrap(),
                Tables\Columns\TextColumn::make('last_analyzed_at')
                    ->label('Ultima analise')
                    ->state(fn(Cursos $record): mixed => data_get($record->auto_status, 'last_analyzed_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('abrir_fluxo')
                    ->label('Abrir fluxo')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(Cursos $record): string => CursosResource::getUrl('index', [
                        'tableAction' => 'autoConfig',
                        'tableActionRecord' => $record->getKey(),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->defaultSort('updated_at', 'desc');
    }

    private function filteredQuery(Builder $query): Builder
    {
        $filters = $this->filters;
        $statuses = $filters['status'] ?? [];
        $statuses = is_array($statuses) ? array_filter($statuses) : (filled($statuses) ? [$statuses] : []);

        if ($statuses !== []) {
            $query->where(function (Builder $builder) use ($statuses): void {
                foreach ($statuses as $status) {
                    $builder->orWhere('auto_status->status', $status);
                }
            });
        }

        return match ($filters['attention'] ?? 'all') {
            'blocking' => $query->whereRaw(
                "JSON_CONTAINS(COALESCE(auto_status, JSON_OBJECT()), JSON_OBJECT('blocking', true), '$.pending_actions')"
            ),
            'breakpoints' => $query->whereRaw(
                "JSON_SEARCH(auto_status, 'one', 'breakpoint', NULL, '$.pending_actions[*].type') IS NOT NULL"
            ),
            'ready' => $query->whereRaw(
                "JSON_SEARCH(auto_steps, 'one', 'ready', NULL, '$.*.status') IS NOT NULL"
            ),
            'errors' => $query->where(function (Builder $builder): void {
                $builder
                    ->where('auto_status->status', 'erro')
                    ->orWhereRaw("JSON_SEARCH(auto_steps, 'one', 'error', NULL, '$.*.status') IS NOT NULL");
            }),
            default => $query,
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'ativo' => 'info',
            'aguardando_acao', 'pausado' => 'warning',
            'erro' => 'danger',
            'concluido' => 'success',
            'cancelado' => 'gray',
            default => 'gray',
        };
    }
}
