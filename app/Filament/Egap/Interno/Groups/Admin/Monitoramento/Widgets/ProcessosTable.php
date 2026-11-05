<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Fontes\ProcessosDoMysql;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProcessosTable extends TabelaDoMonitoramento
{
    public function table(Table $table): Table
    {
        $processos = ProcessosDoMysql::emExecucao();

        return $table
            ->heading('Consultas rodando agora')
            ->description('Conexões ativas neste banco (exceto as ociosas). Acima de 5 s fica em vermelho.')
            ->records(fn (): array => array_map(fn (array $p): array => ['__key' => (string) $p['id'], ...$p], $processos ?? []))
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('usuario')->label('Usuário'),
                TextColumn::make('tempo_seg')->label('Tempo')->formatStateUsing(fn ($state) => $state.' s')
                    ->color(fn ($state) => $state > 5 ? 'danger' : null),
                TextColumn::make('estado')->label('Estado')->placeholder('—'),
                TextColumn::make('sql')->label('SQL')->fontFamily('mono')->size('xs')->wrap()->placeholder('—'),
            ])
            ->paginated(false)
            ->emptyStateHeading($processos === null ? 'Lista de processos indisponível' : 'Nenhuma consulta em execução')
            ->emptyStateDescription($processos === null ? 'O usuário do banco precisa da permissão PROCESS.' : null);
    }
}
