<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Fontes\TabelasDoBanco;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TabelasTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        $tabelas = TabelasDoBanco::maiores(30);
        $myisam = TabelasDoBanco::myisam() ?? [];

        return $table
            ->heading('Maiores tabelas')
            ->description($myisam === []
                ? 'Por espaço em disco (dados + índices).'
                : 'Por espaço em disco. Tabelas MyISAM ('.count($myisam).' no banco: '.implode(', ', array_slice($myisam, 0, 8)).(count($myisam) > 8 ? '…' : '').') não têm transação nem chave estrangeira.')
            ->records(fn (): array => array_map(fn (array $t): array => ['__key' => $t['tabela'], ...$t], $tabelas ?? []))
            ->columns([
                TextColumn::make('tabela')->label('Tabela')->fontFamily('mono')->size('sm'),
                TextColumn::make('motor')->label('Motor')->badge()
                    ->color(fn ($state) => $state === 'MyISAM' ? 'warning' : 'gray'),
                TextColumn::make('linhas')->label('Linhas (estimado)')->numeric(locale: 'pt_BR')->alignEnd(),
                TextColumn::make('dados_mb')->label('Dados')->formatStateUsing(fn ($state) => self::numero($state, 1).' MB')->alignEnd(),
                TextColumn::make('indices_mb')->label('Índices')->formatStateUsing(fn ($state) => self::numero($state, 1).' MB')->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading($tabelas === null ? 'Lista de tabelas indisponível' : 'Nenhuma tabela');
    }
}
