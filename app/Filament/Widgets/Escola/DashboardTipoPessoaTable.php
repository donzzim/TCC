<?php

namespace App\Filament\Widgets\Escola;

use App\Models\Escola\CursoAlunos;
use App\Services\Pedagogico\DashboardPedagogicoService;
use App\Models\Escola\TipoPublico;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class DashboardTipoPessoaTable extends BaseWidget
{
    use InteractsWithPageFilters;

    public int $totalGeral = 0;
    protected static ?int $sort = 8;
    protected ?string $pollingInterval = '100s';
    protected int|string|array $columnSpan = 4;

    public function table(Table $table): Table
    {
        $service = app(DashboardPedagogicoService::class);
        [$inicio, $termino] = $service->periodo($this->filters);
        $cursoIds = $service->cursoIdsNoPeriodo($inicio, $termino);
        $eventoIds = $service->idsCursoEAtividades($cursoIds);

        $this->totalGeral = CursoAlunos::whereIn('curso_id', $eventoIds)->count();

        return $table
            ->heading('Pessoas Capacitadas')
            ->description('Total de Pessoas Capacitadas')
            ->poll('100s')
            ->striped()
            ->query(
                TipoPublico::query()
                    ->leftJoin('user_perfil', 'cad_tabelaapoio.id', '=', 'user_perfil.tipopessoa_id')
                    ->leftJoin('esc_cursoalunos', 'user_perfil.user_id', '=', 'esc_cursoalunos.aluno_id')
                    ->whereIn('esc_cursoalunos.curso_id', $eventoIds)
                    ->select(
                        'cad_tabelaapoio.id',
                        'cad_tabelaapoio.descricao',
                        DB::raw('COUNT(esc_cursoalunos.id) as quantidade')
                    )
                    ->groupBy('cad_tabelaapoio.id', 'cad_tabelaapoio.descricao')
                    ->orderBy('quantidade', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Tipo de Pessoa'),

                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Quantidade')
                    ->alignCenter()
                    ->numeric(0, ',', '.'),

                Tables\Columns\TextColumn::make('progresso')
                    ->label('%')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $percentage = ($this->totalGeral > 0)
                            ? ($record->quantidade / $this->totalGeral) * 100
                            : 0;

                        $bar = '<div class="w-96">
                                    <div class="flex justify-between w-full mb-1">
                                        <span class="text-sm font-medium text-sky-700 dark:text-white">
                                            ' . $record->descricao . '
                                        </span>
                                        <span class="text-sm font-medium text-sky-700 dark:text-white">
                                            ' . round($percentage) . '%
                                        </span>
                                    </div>
                                    <div class="w-full ml-4 bg-slate-200 rounded-full h-2.5 dark:bg-slate-700">
                                        <div
                                            class="bg-sky-600 dark:bg-sky-400 h-2.5 rounded-full"
                                            style="width: ' . round($percentage) . '%"
                                        ></div>
                                    </div>
                                </div>';

                        return new HtmlString($bar);
                    }),
            ])
            ->selectable(false)
            ->recordActions([
                //
            ])
            ->toolBarActions([
                //
            ])
            ->paginated(false);
    }
}
