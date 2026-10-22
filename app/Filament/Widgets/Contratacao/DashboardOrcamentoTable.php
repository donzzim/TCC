<?php

namespace App\Filament\Widgets\Contratacao;

use App\Models\Contratacao\Orcamento;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class DashboardOrcamentoTable extends BaseWidget
{
    use InteractsWithPageFilters;

    public int $saldoAtual = 0;
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '100s';
    protected int|string|array $columnSpan = 8;

    // public function mount(): void
    // {
    //     $this->saldoAtual = Orcamento::sum('saldo_total');
    // }
    public function table(Table $table): Table
    {
        $ano = $this->filters['ano'] ?? now()->year;

        $saldoAtual = Orcamento::where('ano', $ano)->sum('saldo_total');

        return $table
            ->heading('Plano Orcamentário')
            ->description('Total do Orçamento por Natureza de Despesa')
            ->poll('100s')
            ->striped()
            ->query(
                Orcamento::query()->where('ano', $ano)
            )
            ->columns([
                Tables\Columns\TextColumn::make('natureza.descricao')
                    ->label('Natureza de Despesas')
                    ->description(fn(Orcamento $record): string => $record->natureza->codigo)
                    ->wrap(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap(),

                Tables\Columns\ColumnGroup::make('Aprovado', [
                    Tables\Columns\TextColumn::make('valor_1instancia')
                        ->label('1ª Instância')
                        ->alignCenter()
                        ->money('BRL')
                        ->badge()
                        ->color('success')
                        ->summarize(
                            Sum::make()
                                ->label(' ')
                                ->extraAttributes(['class' => 'font-bold'])
                                ->money('BRL', locale: 'pt')
                        ),

                    Tables\Columns\TextColumn::make('valor_2instancia')
                        ->label('2ª Instância')
                        ->alignCenter()
                        ->money('BRL')
                        ->badge()
                        ->color('success')
                        ->summarize(
                            Sum::make()
                                ->label(' ')
                                ->extraAttributes(['class' => 'font-bold'])
                                ->money('BRL', locale: 'pt')
                        ),

                    Tables\Columns\TextColumn::make('valor_total')
                        ->label('Total')
                        ->alignCenter()
                        ->money('BRL')
                        ->badge()
                        ->color('success')
                        ->summarize(
                            Sum::make()
                                ->label(' ')
                                ->extraAttributes(['class' => 'font-bold'])
                                ->money('BRL', locale: 'pt')
                        ),
                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader(),

                Tables\Columns\ColumnGroup::make('Saldo', [
                    Tables\Columns\TextColumn::make('saldo_1instancia')
                        ->label('1ª Instância')
                        ->alignCenter()
                        ->money('BRL')
                        ->badge()
                        ->color('warning')
                        ->summarize(
                            Sum::make()
                                ->label(' ')
                                ->extraAttributes(['class' => 'font-bold'])
                                ->money('BRL', locale: 'pt')
                        ),

                    Tables\Columns\TextColumn::make('saldo_2instancia')
                        ->label('2ª Instância')
                        ->alignCenter()
                        ->money('BRL')
                        ->badge()
                        ->color('warning')
                        ->summarize(
                            Sum::make()
                                ->label(' ')
                                ->extraAttributes(['class' => 'font-bold'])
                                ->money('BRL', locale: 'pt')
                        ),

                    Tables\Columns\TextColumn::make('saldo_total')
                        ->label('Total')
                        ->alignCenter()
                        ->money('BRL')
                        ->badge()
                        ->color('warning')
                        ->summarize(
                            Sum::make()
                                ->label(' ')
                                ->extraAttributes(['class' => 'font-bold'])
                                ->money('BRL', locale: 'pt')
                        ),
                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader(),

                Tables\Columns\TextColumn::make('progresso')
                    ->label('%')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $percentage = ($this->saldoAtual > 0)
                            ? (float) ($record->saldo_total / $this->saldoAtual) * 100
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
