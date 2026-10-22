<?php

namespace App\Filament\Widgets\Contratacao;

use App\Models\Contratacao\Orcamento;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoEvento;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class DashboardContratacaoTable extends BaseWidget
{
    public int $saldoAtual = 0;
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '100s';
    protected int|string|array $columnSpan = 8;
    public function mount(): void
    {
        $this->saldoAtual = Orcamento::sum('saldo_total');
    }
    public function table(Table $table): Table
    {
        return $table
            ->heading('Contratações')
            ->description('Total de Processos por Tipo de Contratação')
            ->poll('100s')
            ->striped()
            ->query(
                ProcessoEvento::query()
            )
            ->columns([
                Tables\Columns\TextColumn::make('tipoevento.descricao')
                    ->label('Evento')
                    // ->description(fn(ProcessoEvento $record): string => $record->tipoevento->codigo)
                    ->wrap(),

                Tables\Columns\TextColumn::make('processo.descricao')
                    ->label('Processo')
                    ->wrap()
                    ->html()
                    ->description(fn(ProcessoEvento $record): string => $record->processo->numero),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap(),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('progresso')
                    ->label('%')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $percentage = ($this->saldoAtual > 0)
                            ? (float) ($record->valor / $this->saldoAtual) * 100
                            : 0;

                        $perc = number_format($percentage, 2, ',', '.');
                        $bar = '<div class="w-auto">
                                    <div class="flex justify-between w-full mb-1">
                                        <span class="text-sm font-medium text-sky-700 dark:text-white"></span>
                                        <span class="text-sm font-medium text-sky-700 dark:text-white">
                                            ' . $perc . '%
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
