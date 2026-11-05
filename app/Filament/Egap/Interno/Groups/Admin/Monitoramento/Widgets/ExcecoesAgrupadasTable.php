<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Logs\AgrupadorDeExcecoes;
use App\Monitoramento\Logs\LeitorDeLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExcecoesAgrupadasTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Erros agrupados')
            ->description('Do log mais recente (últimos 2 MB), por tipo de exceção e local no código.')
            ->records(fn (): array => array_map(
                fn (array $g, int $i): array => ['__key' => (string) $i, ...$g],
                $grupos = $this->grupos(),
                array_keys($grupos)
            ))
            ->columns([
                TextColumn::make('qtd')->label('Vezes')->numeric()->alignEnd(),
                TextColumn::make('classe')->label('Exceção')->fontFamily('mono')->size('sm')->wrap()
                    ->description(fn (array $record) => $record['mensagem']),
                TextColumn::make('local')->label('Local')->fontFamily('mono')->size('xs')->wrap(),
                TextColumn::make('url')->label('URL')->size('xs')->wrap()->placeholder('—'),
                TextColumn::make('ultima')->label('Última'),
                TextColumn::make('primeira')->label('Primeira'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nenhum erro no trecho lido do log.');
    }

    private function grupos(): array
    {
        $leitor = new LeitorDeLog;
        $arquivo = $leitor->arquivos()[0] ?? null;

        return $arquivo === null ? [] : AgrupadorDeExcecoes::agrupar($leitor->ultimas($arquivo, limite: 5000));
    }
}
