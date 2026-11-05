<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Pages\Monitoramento;
use App\Monitoramento\Leitura\LinhaDeRota;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RotasLentasTable extends TabelaDoMonitoramento
{
    protected const TITULO = 'Rotas que mais pesam';

    protected const DESCRICAO = 'Tempo total = chamadas × duração: mostra o que mais consome o servidor. Inclui a API do app.';

    protected const VAZIO = 'Nenhuma requisição registrada no período.';

    protected bool $somenteLivewire = false;

    protected int $limite = 50;

    public function table(Table $table): Table
    {
        return $table
            ->heading(static::TITULO)
            ->description(static::DESCRICAO)
            ->records(fn (?array $filters): array => $this->linhas($filters['ordem']['value'] ?? 'tempo_total'))
            ->filters([
                SelectFilter::make('ordem')
                    ->label('Ordenar por')
                    ->options([
                        'tempo_total' => 'Tempo total',
                        'p95' => 'p95',
                        'qtd' => 'Chamadas',
                        'sql' => 'Consultas SQL por requisição',
                    ])
                    ->default('tempo_total')
                    ->selectablePlaceholder(false),
            ])
            ->columns([
                TextColumn::make('rota')->label('Rota')->wrap()->fontFamily('mono')->size('sm')
                    ->description(fn (array $record): string => Monitoramento::CONTEXTOS[$record['contexto']] ?? $record['contexto']),
                TextColumn::make('qtd')->label('Chamadas')->numeric(locale: 'pt_BR')->alignEnd(),
                TextColumn::make('p50')->label('p50')->formatStateUsing(fn ($state) => self::percentil($state))->alignEnd(),
                TextColumn::make('p95')->label('p95')->formatStateUsing(fn ($state) => self::percentil($state))->alignEnd()
                    ->color(fn ($state) => $state !== null && $state > 1000 ? 'warning' : null),
                TextColumn::make('maxMs')->label('Máx.')->formatStateUsing(fn ($state) => self::ms($state))->alignEnd(),
                TextColumn::make('tempoTotalMs')->label('Tempo total')->formatStateUsing(fn ($state) => self::ms($state))->alignEnd(),
                TextColumn::make('sqlMediaQtd')->label('SQL/req.')->formatStateUsing(fn ($state) => self::numero($state, 1))->alignEnd()
                    ->color(fn ($state) => $state > 100 ? 'danger' : null),
                TextColumn::make('percentualSql')->label('% em SQL')->formatStateUsing(fn ($state) => self::numero($state).'%')->alignEnd(),
                TextColumn::make('maxMemoriaMb')->label('Memória')->formatStateUsing(fn ($state) => self::numero($state, 1).' MB')->alignEnd(),
                TextColumn::make('alerta')->label('Alertas')
                    ->state(fn (array $record): ?string => $record['nMaisUm'] > 0 ? 'N+1' : null)
                    ->badge()
                    ->color('warning')
                    ->tooltip(fn (array $record) => $record['nMaisUm'] > 0 ? "{$record['nMaisUm']} requisições repetiram a mesma consulta 20 vezes ou mais" : null),
                TextColumn::make('erros5xx')->label('5xx')->numeric()->alignEnd()
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),
            ])
            ->paginated(false)
            ->emptyStateHeading(static::VAZIO)
            ->emptyStateDescription('Confira se o monitoramento está ativo e se o agendador está rodando.');
    }

    private function linhas(string $ordem): array
    {
        return array_map(fn (LinhaDeRota $l): array => [
            '__key' => $l->contexto.'|'.$l->rota,
            'contexto' => $l->contexto,
            'rota' => $l->rota,
            'qtd' => $l->qtd,
            'p50' => $l->p50,
            'p95' => $l->p95,
            'maxMs' => $l->maxMs,
            'tempoTotalMs' => $l->tempoTotalMs,
            'sqlMediaQtd' => $l->sqlMediaQtd,
            'percentualSql' => $l->percentualSql,
            'maxMemoriaMb' => $l->maxMemoriaMb,
            'nMaisUm' => $l->nMaisUm,
            'erros5xx' => $l->erros5xx,
        ], $this->repositorio()->rotas($this->periodoAtual(), $this->contexto, $ordem, $this->limite, $this->agora(), $this->somenteLivewire));
    }
}
