<?php

namespace App\Filament\App\Widgets;

use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\TipoLocal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class UltimasAttWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Frequências da Semana';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(
                CursoFrequencias::query()
                    ->with('data')
                    ->where('aluno_id', $user->id)
                    ->whereHas('data', function ($q) {
                        $q->where('inicio', '>=', now()->startOfWeek())
                            ->where('termino', '<=', now()->endOfWeek());
                    })

            )
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Curso')
                    ->getStateUsing(function ($record) {
                        return $record->curso->titulo ?? $record->atividade->titulo;
                    })
                    ->description(function ($record) {
                        if ($record->atividade) {
                            return $record->atividade->curso->titulo;
                        }
                    })
                    ->tooltip(function ($record) {
                        return $record->curso->titulo ?? $record->atividade->titulo;
                    }),

                // Tables\Columns\TextColumn::make('titulo')
                //     ->label('Atividade')
                //     ->searchable()
                //     ->limit(25),

                Tables\Columns\TextColumn::make('data.inicio')
                    ->label(new HtmlString('Data/Hora<br>Inicio'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color(function ($record) {
                        $dias = now()->startOfDay()->diffInDays(
                            Carbon::parse($record->data->inicio)->startOfDay()
                        );

                        // dd($record, $dias);
                        if ($dias < 0) {
                            return 'warning';
                        }
                        if ($dias > 0) {
                            return 'info';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('situacaofrequencia.descricao')
                    ->label('Situação')
                    ->badge()
                    ->getStateUsing(function ($record) {

                        if ($record->situacaofrequencia_id == config('settings.frequencia.ausente')) {
                            if ($record->data->termino > now()) {
                                return 'Não Registrado';
                            } else {
                                return 'Ausente';
                            }
                        }

                        return 'Presente';
                    })
                    ->color(function (string $state) {
                        switch ($state) {
                            case 'Presente':
                                $cor = 'success';
                                break;
                            case 'Ausente':
                                $cor = 'danger';
                                break;
                            default:
                                $cor = 'info';
                                break;
                        }

                        return $cor;
                    }),
                Tables\Columns\TextColumn::make('local')
                    ->label('Local')
                    ->getStateUsing(function ($record) {
                        return TipoLocal::find($record->data->local_id)->descricao ?? 'A definir';
                    }),
            ])
            ->actions([
                Action::make('meuscursos')
                    ->hiddenLabel()
                    ->size('xl')
                    ->icon('heroicon-o-book-open')
                    ->tooltip('Meus Cursos')
                    ->url(fn ($record) => route('filament.app.resources.meus-cursos.index', [
                        // dd($record),
                        'curso' => $record->curso->uuid ?? $record->atividade->uuid,
                    ])),
                Action::make('acessar')
                    ->label('Acessar')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->link)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record->link)),
            ])
            ->defaultSort('data.inicio', 'desc')
            ->toolbarActions([])
            ->emptyStateHeading('Nenhuma atividade próxima')
            ->emptyStateDescription('Não há atividades agendadas para os próximos dias.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
