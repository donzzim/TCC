<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Pages\Monitoramento;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequisicoesLentasTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Requisições lentas, com erro ou amostradas')
            ->description('Guardadas com detalhe: requisições a partir de 1 s, respostas 5xx e 1% das demais.')
            ->records(fn (): array => $this->linhas())
            ->columns([
                TextColumn::make('quando')->label('Quando'),
                TextColumn::make('rota')->label('Rota')->wrap()->fontFamily('mono')->size('sm')
                    ->description(fn (array $record): string => (Monitoramento::CONTEXTOS[$record['contexto']] ?? $record['contexto']).' · '.$record['metodo']),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (int $state): string => $state >= 500 ? 'danger' : ($state >= 400 ? 'warning' : 'gray')),
                TextColumn::make('duracaoMs')->label('Duração')->formatStateUsing(fn ($state) => self::ms($state))->alignEnd(),
                TextColumn::make('sql')->label('SQL')->alignEnd()
                    ->color(fn (array $record) => $record['sqlQtd'] > 100 ? 'danger' : null),
                TextColumn::make('memoriaMb')->label('Memória')->formatStateUsing(fn ($state) => self::numero($state, 1).' MB')->alignEnd(),
                TextColumn::make('requestId')->label('Request ID')->fontFamily('mono')->size('xs')->copyable(),
            ])
            ->recordActions([
                Action::make('detalhes')
                    ->label('Detalhes')
                    ->icon('heroicon-o-magnifying-glass')
                    ->modalHeading('Detalhe da requisição')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(fn (array $record) => view('egap.monitoramento.detalhe-requisicao', [
                        'registro' => $this->repositorio()->requisicao($record['requestId']),
                    ])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nenhuma requisição lenta no período.');
    }

    private function linhas(): array
    {
        $comData = $this->periodoAtual()->usaHoras() || $this->periodoAtual()->segundos() > 3600;

        return array_map(fn (RegistroDeRequisicao $r): array => [
            '__key' => $r->requestId,
            'quando' => now()->setTimestamp((int) $r->instante)->format($comData ? 'd/m H:i:s' : 'H:i:s'),
            'contexto' => $r->contexto,
            'rota' => $r->rota,
            'metodo' => $r->metodo,
            'status' => $r->status,
            'duracaoMs' => $r->duracaoMs,
            'sql' => self::ms($r->sqlMs).' · '.$r->sqlQtd.' consultas',
            'sqlQtd' => $r->sqlQtd,
            'memoriaMb' => $r->memoriaMb,
            'requestId' => $r->requestId,
        ], $this->repositorio()->requisicoesLentas($this->periodoAtual(), $this->contexto, 100, $this->agora()));
    }
}
