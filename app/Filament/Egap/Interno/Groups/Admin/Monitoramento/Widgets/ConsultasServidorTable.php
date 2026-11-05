<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Fontes\ConsultasDoServidor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultasServidorTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        $consultas = ConsultasDoServidor::topPorTempo(20);

        return $table
            ->heading('Consultas que mais consomem o MySQL')
            ->description('Acumulado desde que o MySQL subiu (performance_schema), de toda aplicação que usa este banco. Muitas linhas examinadas para poucas retornadas indica índice faltando.')
            ->records(fn (): array => array_map(fn (array $c, int $i): array => [
                '__key' => (string) $i,
                ...$c,
                'proporcao' => $c['linhas_examinadas'] / max(1, $c['linhas_retornadas']),
            ], $consultas ?? [], array_keys($consultas ?? [])))
            ->columns([
                TextColumn::make('sql')->label('SQL normalizada')->fontFamily('mono')->size('xs')->wrap(),
                TextColumn::make('execucoes')->label('Execuções')->numeric(locale: 'pt_BR')->alignEnd(),
                TextColumn::make('tempo_total_ms')->label('Tempo total')->formatStateUsing(fn ($state) => self::ms($state))->alignEnd(),
                TextColumn::make('media_ms')->label('Média')->formatStateUsing(fn ($state) => self::ms($state))->alignEnd(),
                TextColumn::make('linhas_examinadas')->label('Linhas examinadas')->numeric(locale: 'pt_BR')->alignEnd(),
                TextColumn::make('linhas_retornadas')->label('Linhas retornadas')->numeric(locale: 'pt_BR')->alignEnd(),
                TextColumn::make('proporcao')->label('Alerta')->badge()->color('warning')
                    ->formatStateUsing(fn ($state) => $state > 100 ? 'possível índice faltando' : ''),
            ])
            ->paginated(false)
            ->emptyStateHeading($consultas === null ? 'Estatísticas indisponíveis' : 'Sem estatísticas ainda')
            ->emptyStateDescription($consultas === null ? 'Requer performance_schema ligado e permissão de leitura nele.' : null);
    }
}
