<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Tables;

use App\Filament\Egap\Support\LegacyFileUrl;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Processo\ProcessoResource;

class AnexosProcessosTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('processoRelacaoRef.num_processo', 'Processo', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TableColumns::text('tipoDocumentoRelacaoRef.descricao', 'Tipo de Documento'),

                TableColumns::date('data', 'Data'),

                TableColumns::text('num_documento', 'Nº Documento'),

                TableColumns::text('materialRelacaoRef.Descricao', 'Material')
                    ->wrap()
                    ->limit(50),

                TableColumns::text('anexo_documento', 'Anexo')
                    ->url(fn($record): ?string => LegacyFileUrl::resolve($record->anexo_documento, config('app.egap')))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($record) => $record->anexo_documento ? 'Abrir' : '-')
                    ->color('primary')
                    ->weight('bold'),

                TableColumns::dateTime('date_time', 'Atualizado em'),
            ])
            ->filters([

            ]);
    }
}
