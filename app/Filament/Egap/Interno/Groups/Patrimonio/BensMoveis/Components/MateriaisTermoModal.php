<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MateriaisTermoModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $termoId;

    public function mount(int $termoId): void
    {
        $this->termoId = $termoId;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getMateriaisQuery())
            ->columns([
                TableColumns::text('NumPatrimonio', 'Patrimônio', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TextColumn::make('bem.Descricao')
                    ->label('Descrição')
                    ->default('-')
                    ->weight('medium')
                    ->wrap(),

                TextColumn::make('origem')
                    ->label('Origem')
                    ->getStateUsing(fn(TransferenciaBemMovel $record): string => self::unidade(
                        $record->unidadeAnteriorRel?->unidadeorganizacional,
                        $record->UnidadeAnterior,
                    ))
                    ->description(fn(TransferenciaBemMovel $record): ?string => self::setor(
                        $record->setorAnteriorRel?->setor,
                        $record->complementoAnteriorRel?->descricao,
                        $record->SetorAnterior,
                    ))
                    ->wrap(),

                TextColumn::make('destino')
                    ->label('Destino')
                    ->getStateUsing(fn(TransferenciaBemMovel $record): string => self::unidade(
                        $record->unidadeAtualRel?->unidadeorganizacional,
                        $record->UnidadeAtual,
                    ))
                    ->description(fn(TransferenciaBemMovel $record): ?string => self::setor(
                        $record->setorAtualRel?->setor,
                        $record->complementoAtualRel?->descricao,
                        $record->SetorAtual,
                    ))
                    ->wrap(),

                TableColumns::text('usuarioRef.name', 'Movimentado por')
                    ->description(fn(TransferenciaBemMovel $record): string => $record->date_time?->format('d/m/Y H:i') ?? '-'),
            ])
            ->defaultSort('id')
            ->defaultPaginationPageOption(15)
            ->paginated([15])
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateHeading('Nenhum material está vinculado a este termo')
            ->recordActions([])
            ->toolbarActions([]);
    }

    private function getMateriaisQuery(): Builder
    {
        return TransferenciaBemMovel::query()
            ->where('Termo', $this->termoId)
            ->with([
                'bem',
                'unidadeAnteriorRel',
                'setorAnteriorRel',
                'complementoAnteriorRel',
                'unidadeAtualRel',
                'setorAtualRel',
                'complementoAtualRel',
                'usuarioRef',
            ]);
    }

    private static function unidade(
        ?string $unidade,
        mixed $unidadeId,
    ): string {
        return $unidade ?: ($unidadeId ? "Unidade {$unidadeId}" : '-');
    }

    private static function setor(
        ?string $setor,
        ?string $complemento,
        mixed $setorId,
    ): ?string {
        $partes = array_filter([
            $setor ?: ($setorId ? "Setor {$setorId}" : null),
            $complemento,
        ]);

        return $partes ? implode(' / ', $partes) : null;
    }
}
