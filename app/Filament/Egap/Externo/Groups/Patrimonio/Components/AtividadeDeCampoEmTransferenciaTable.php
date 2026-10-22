<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bens em transferência com termo ainda pendente de assinatura/invalidado
 * (legado: atividades.php, botão "Em Transferência" + api/atividades.api.php
 * -> $_POST['transferencia']), sem recorte por setor. Transferências já
 * assinadas ou canceladas não aparecem aqui (mesmo comportamento do legado).
 *
 * O "Tipo" (Entrega/Devolução/Manutenção/Transferência) e a "Região" seguem
 * exatamente as regras fixas do legado (setores-polo e listas de unidades por
 * região) para preservar a mesma classificação usada pela Comissão.
 */
class AtividadeDeCampoEmTransferenciaTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const SETORES_ENTREGA = [1239, 801, 1023];

    private const COMPLEMENTO_DEVOLUCAO = 'DEPÓSITO - BENS USADOS - LOGÍSTICA REVERSA';

    private const COMPLEMENTO_MANUTENCAO = 'MANUTENÇÃO';

    private const COMPLEMENTO_TESTE = 'DEPÓSITO - TESTE';

    private const REGIAO_0 = [1237];

    private const REGIAO_1 = [173, 347, 545, 746, 766, 783, 789, 819, 822, 866, 2009, 2010, 2290, 2293, 2297, 2298, 2300, 2317, 2318];

    private const REGIAO_2 = [74, 123, 230, 473, 525, 533, 601, 629, 686, 709, 757, 976, 1009, 1015, 1016];

    private const REGIAO_3 = [89, 102, 205, 277, 320, 418, 443, 497, 615, 671, 1003, 1004, 1007, 1060, 1062];

    private const REGIAO_4 = [1, 38, 53, 140, 194, 306, 337, 429, 648, 658, 1006, 1011, 1012, 1013, 1020, 1211];

    private const REGIAO_5 = [20, 64, 132, 241, 251, 268, 293, 451, 464, 510, 578, 588, 639, 699, 1005, 1008, 1010, 1014];

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('NumPatrimonio', 'Patrimônio', isFirstColumn: true)
                    ->badge()
                    ->color('primary')
                    ->description(fn(BemMovel $record): ?string => filled($record->NumerodePatAnterior)
                        ? "Antigo: {$record->NumerodePatAnterior}"
                        : null),

                TableColumns::text('Descricao', 'Descrição')
                    ->wrap()
                    ->description(fn(BemMovel $record): ?string => collect([
                        $record->marcaRef?->Descricao,
                        $record->modeloRef?->descricao,
                        filled($record->EstadodeConservacao) ? "Estado: {$record->EstadodeConservacao}" : null,
                    ])->filter()->implode(' / ') ?: null),

                TableColumns::text('setor_origem', 'Setor de Origem')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $record->ultimaTransferencia?->setorAnteriorRel?->setor ?? '-')
                    ->description(fn(BemMovel $record): ?string => collect([
                        $record->ultimaTransferencia?->complementoAnteriorRel?->descricao,
                        $this->regiao($record->ultimaTransferencia?->UnidadeAnterior),
                    ])->filter()->implode(' • ') ?: null)
                    ->wrap(),

                TableColumns::text('setor_destino', 'Setor de Destino')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $record->ultimaTransferencia?->setorAtualRel?->setor ?? '-')
                    ->description(fn(BemMovel $record): ?string => collect([
                        $record->ultimaTransferencia?->complementoAtualRel?->descricao,
                        $this->regiao($record->ultimaTransferencia?->UnidadeAtual),
                    ])->filter()->implode(' • ') ?: null)
                    ->wrap(),

                TableColumns::text('tipo', 'Tipo')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $this->classificarTipo($record))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Entrega' => 'success',
                        'Devolução' => 'info',
                        'Manutenção' => 'warning',
                        default => 'primary',
                    }),

                TableColumns::text('situacao_termo', 'Situação do Termo')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $this->situacaoTermoLabel($record))
                    ->badge()
                    ->color(fn($state, BemMovel $record): string => (int) ($record->ultimaTransferencia?->termoRel?->arquivoDigital?->situacao ?? 0) === ArquivoDigital::SITUACAO_INVALIDADO
                        ? 'danger'
                        : 'warning'),

                TableColumns::text('observacao_termo', 'Observação')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $record->ultimaTransferencia?->termoRel?->arquivoDigital?->observacao ?? '-')
                    ->wrap()
                    ->limit(80),

                TableColumns::text('termo', 'Termo')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $record->ultimaTransferencia?->termoRel?->termo_completo ?? '-')
                    ->badge()
                    ->color('gray')
                    ->url(fn(BemMovel $record): ?string => $record->ultimaTransferencia?->Termo
                        ? route('termo.imprimir', ['id' => $record->ultimaTransferencia->Termo])
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                $this->tabelaAnaliticaAction(),
                $this->exportarAction(),
            ])
            ->defaultSort('NumPatrimonio')
            ->emptyStateHeading('Nenhum bem em transferência pendente encontrado.');
    }

    private function tabelaAnaliticaAction(): Action
    {
        return Action::make('tabela_analitica')
            ->label('Tabela Analítica')
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->modalHeading('Tabela Analítica - Em Transferência por Setor de Destino')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn(): View => view(
                'egap.filament.pages.externo.patrimonio.partials.atividade-de-campo-tabela-analitica',
                ['linhas' => $this->tabelaAnaliticaDados()],
            ));
    }

    private function tabelaAnaliticaDados(): Collection
    {
        return $this->getQuery()->get()
            ->groupBy(fn(BemMovel $record): string => $record->ultimaTransferencia?->setorAtualRel?->setor ?? 'Não informado')
            ->map(function (Collection $bens, string $setor): array {
                $porTipo = $bens->groupBy(fn(BemMovel $record): string => $this->classificarTipo($record));

                return [
                    'setor' => $setor,
                    'entrega' => $porTipo->get('Entrega', collect())->count(),
                    'manutencao' => $porTipo->get('Manutenção', collect())->count(),
                    'devolucao' => $porTipo->get('Devolução', collect())->count(),
                    'transferencia' => $porTipo->get('Transferência', collect())->count(),
                    'total' => $bens->count(),
                ];
            })
            ->sortKeys()
            ->values();
    }

    private function exportarAction(): Action
    {
        return Action::make('exportar')
            ->label('Exportar CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(fn(): StreamedResponse => $this->exportCsv());
    }

    private function exportCsv(): StreamedResponse
    {
        $records = $this->getQuery()->get();
        $filename = 'inventario_em_transferencia_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Patrimônio',
                'Patrimônio Antigo',
                'Descrição',
                'Marca',
                'Modelo',
                'Estado',
                'Região Origem',
                'Unidade Origem',
                'Setor Origem',
                'Complemento Origem',
                'Região Destino',
                'Unidade Destino',
                'Setor Destino',
                'Complemento Destino',
                'Tipo',
                'Observação',
                'Situação do Termo',
                'Termo',
            ], ';');

            foreach ($records as $record) {
                $transferencia = $record->ultimaTransferencia;

                fputcsv($handle, [
                    $record->NumPatrimonio,
                    $record->NumerodePatAnterior,
                    $record->Descricao,
                    $record->marcaRef?->Descricao,
                    $record->modeloRef?->descricao,
                    $record->EstadodeConservacao,
                    $this->regiao($transferencia?->UnidadeAnterior),
                    $transferencia?->unidadeAnteriorRel?->unidadeorganizacional,
                    $transferencia?->setorAnteriorRel?->setor,
                    $transferencia?->complementoAnteriorRel?->descricao,
                    $this->regiao($transferencia?->UnidadeAtual),
                    $transferencia?->unidadeAtualRel?->unidadeorganizacional,
                    $transferencia?->setorAtualRel?->setor,
                    $transferencia?->complementoAtualRel?->descricao,
                    $this->classificarTipo($record),
                    $transferencia?->termoRel?->arquivoDigital?->observacao,
                    $this->situacaoTermoLabel($record),
                    $transferencia?->termoRel?->termo_completo,
                ], ';');
            }

            fclose($handle);
        }, $filename);
    }

    private function classificarTipo(BemMovel $record): string
    {
        $transferencia = $record->ultimaTransferencia;
        $setorOrigemId = $transferencia?->SetorAnterior;
        $setorDestinoId = $transferencia?->SetorAtual;
        $complementoDestino = strtoupper(trim((string) $transferencia?->complementoAtualRel?->descricao));

        if (
            in_array($setorOrigemId, self::SETORES_ENTREGA, true)
            && $setorOrigemId !== $setorDestinoId
            && ! in_array($complementoDestino, [self::COMPLEMENTO_DEVOLUCAO, self::COMPLEMENTO_MANUTENCAO, self::COMPLEMENTO_TESTE], true)
        ) {
            return 'Entrega';
        }

        return match ($complementoDestino) {
            self::COMPLEMENTO_DEVOLUCAO => 'Devolução',
            self::COMPLEMENTO_MANUTENCAO => 'Manutenção',
            default => 'Transferência',
        };
    }

    private function situacaoTermoLabel(BemMovel $record): string
    {
        return (int) ($record->ultimaTransferencia?->termoRel?->arquivoDigital?->situacao ?? 0) === ArquivoDigital::SITUACAO_INVALIDADO
            ? 'Invalidado'
            : 'Pendente';
    }

    private function regiao(?int $unidadeId): ?string
    {
        return match (true) {
            $unidadeId === null => null,
            in_array($unidadeId, self::REGIAO_0, true) => 'REGIÃO 0',
            in_array($unidadeId, self::REGIAO_1, true) => 'REGIÃO 1',
            in_array($unidadeId, self::REGIAO_2, true) => 'REGIÃO 2',
            in_array($unidadeId, self::REGIAO_3, true) => 'REGIÃO 3',
            in_array($unidadeId, self::REGIAO_4, true) => 'REGIÃO 4',
            in_array($unidadeId, self::REGIAO_5, true) => 'REGIÃO 5',
            default => null,
        };
    }

    private function getQuery(): Builder
    {
        return BemMovel::query()
            ->where('acuracia', 'emTransferencia')
            ->whereIn('SituacaoBem', [1, 8])
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('ultimaTransferencia.termoRel.arquivoDigital')
                    ->orWhereHas(
                        'ultimaTransferencia.termoRel.arquivoDigital',
                        fn(Builder $query) => $query->whereNotIn('situacao', [
                            ArquivoDigital::SITUACAO_VALIDADO,
                            ArquivoDigital::SITUACAO_CANCELADO,
                            4,
                        ])
                    );
            })
            ->with([
                'marcaRef',
                'modeloRef',
                'ultimaTransferencia.setorAnteriorRel',
                'ultimaTransferencia.setorAtualRel',
                'ultimaTransferencia.unidadeAnteriorRel',
                'ultimaTransferencia.unidadeAtualRel',
                'ultimaTransferencia.complementoAnteriorRel',
                'ultimaTransferencia.complementoAtualRel',
                'ultimaTransferencia.termoRel.arquivoDigital',
            ]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
