<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
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
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bens não localizados na conferência do inventário online (legado:
 * atividades.php, botão "Não Localizados" + api/atividades.api.php ->
 * $_POST['NaoLocalizados']), sem recorte por setor: reflete a visão da
 * Comissão sobre todo o patrimônio ativo (SituacaoBem = 1) marcado como não
 * encontrado.
 */
class AtividadeDeCampoNaoLocalizadosTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

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
                    ])->filter()->implode(' / ') ?: null),

                TableColumns::text('setorRef.setor', 'Setor')
                    ->wrap(),

                TableColumns::text('complementoSetorRef.descricao', 'Complemento')
                    ->wrap(),

                TableColumns::text('elementoDespesaRef.DescricaodaClasse', 'Elemento de Despesa')
                    ->wrap(),

                TableColumns::date('DatadeIncorporacao', 'Aquisição'),

                TableColumns::money('ValorAquisicao', 'Valor de Aquisição'),

                TableColumns::money('Valor', 'Valor Líquido Contábil'),

                TableColumns::text('Observacao', 'Observação')
                    ->wrap()
                    ->limit(80),

                TableColumns::text('sit_inventario', 'Situação')
                    ->formatStateUsing(fn(BemMovel $record): string => collect([
                        strtoupper(trim((string) $record->sit_inventario)) ?: 'A INVENTARIAR',
                        $record->inventarioRef ? "{$record->inventarioRef->num_inventario}/{$record->inventarioRef->ano_inventario}" : null,
                    ])->filter()->implode(' - '))
                    ->badge()
                    ->color(fn(BemMovel $record): string => match (strtoupper(trim((string) $record->sit_inventario))) {
                        'LOCALIZADO/AJUSTES', 'EM TRANSFERENCIA' => 'warning',
                        'LOCALIZADO' => 'success',
                        'NÃO LOCALIZADO', 'NAO LOCALIZADO' => 'danger',
                        default => 'gray',
                    }),

                TableColumns::text('termo', 'Termo')
                    ->formatStateUsing(fn($state, BemMovel $record): string => $record->ultimaTransferenciaValidada?->termoRel?->termo_completo ?? '-')
                    ->description(fn(BemMovel $record): ?string => $this->assinaturaTermo($record))
                    ->badge()
                    ->color(fn(BemMovel $record): string => $record->ultimaTransferenciaValidada ? 'success' : 'gray')
                    ->url(fn(BemMovel $record): ?string => filled($record->ultimaTransferenciaValidada?->termoRel?->arquivoDigital?->arquivo_digital)
                        ? config('app.egap') . $record->ultimaTransferenciaValidada->termoRel->arquivoDigital->arquivo_digital
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                $this->exportarAction(),
            ])
            ->defaultSort('NumPatrimonio')
            ->emptyStateHeading('Nenhum bem não localizado encontrado.');
    }

    private function assinaturaTermo(BemMovel $record): ?string
    {
        $arquivoDigital = $record->ultimaTransferenciaValidada?->termoRel?->arquivoDigital;

        if (! $arquivoDigital) {
            return null;
        }

        return collect([
            $arquivoDigital->validadoPor?->name,
            $arquivoDigital->data_validacao?->format('d/m/Y'),
        ])->filter()->implode(' em ') ?: null;
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
        $filename = 'inventario_nao_localizados_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Patrimônio',
                'Patrimônio Antigo',
                'Descrição',
                'Marca',
                'Modelo',
                'Elemento de Despesa',
                'Data de Aquisição',
                'Valor de Aquisição',
                'Valor Líquido Contábil',
                'Setor',
                'Complemento do Setor',
                'Observação',
                'Situação Inventário',
                'Termo',
                'Assinado por',
            ], ';');

            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->NumPatrimonio,
                    $record->NumerodePatAnterior,
                    $record->Descricao,
                    $record->marcaRef?->Descricao,
                    $record->modeloRef?->descricao,
                    $record->elementoDespesaRef?->DescricaodaClasse,
                    optional($record->DatadeIncorporacao)->format('d/m/Y'),
                    $record->ValorAquisicao,
                    $record->Valor,
                    $record->setorRef?->setor,
                    $record->complementoSetorRef?->descricao,
                    $record->Observacao,
                    $record->sit_inventario,
                    $record->ultimaTransferenciaValidada?->termoRel?->termo_completo,
                    $record->ultimaTransferenciaValidada?->termoRel?->arquivoDigital?->validadoPor?->name,
                ], ';');
            }

            fclose($handle);
        }, $filename);
    }

    private function getQuery(): Builder
    {
        return BemMovel::query()
            ->where('acuracia', 'NaoLocalizado')
            ->where('SituacaoBem', 1)
            ->with([
                'marcaRef',
                'modeloRef',
                'setorRef',
                'complementoSetorRef',
                'elementoDespesaRef',
                'inventarioRef',
                'ultimaTransferenciaValidada.termoRel.arquivoDigital.validadoPor',
            ]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
