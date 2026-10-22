<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\AtividadeInventario;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class AtividadeDeCampoResumoTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const SITUACOES_ATIVAS = [1, 7, 8];

    public ?int $inventarioId = null;

    private ?Collection $atividadesPorSetor = null;

    private ?Collection $resumosPorGrupo = null;

    public function mount(?int $inventarioId = null): void
    {
        $this->inventarioId = $inventarioId;
    }

    #[On('inventario-selecionado')]
    public function atualizarInventarioSelecionado(?int $inventario): void
    {
        $this->inventarioId = $inventario;
        $this->atividadesPorSetor = null;
        $this->resumosPorGrupo = null;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('Setor')
                    ->label('Unidade / Setor')
                    ->formatStateUsing(fn(BemMovel $record): string => $record->setorRef?->rotuloInventario() ?? "Setor {$record->Setor}")
                    ->wrap(),

                TextColumn::make('ComplementoSetor')
                    ->label('Complemento')
                    ->formatStateUsing(fn(BemMovel $record): string => $record->complementoSetorRef?->descricao ?? '-')
                    ->wrap(),

                TextColumn::make('a_inventariar')
                    ->label('A Inventariar')
                    ->getStateUsing(fn(BemMovel $record): int => $this->resumoDoGrupo($record)['a_inventariar'])
                    ->alignCenter()
                    ->numeric()
                    ->summarize($this->totalizadorResumo('a_inventariar')),

                TextColumn::make('localizado')
                    ->label('Localizado')
                    ->getStateUsing(fn(BemMovel $record): int => $this->resumoDoGrupo($record)['localizado'])
                    ->alignCenter()
                    ->numeric()
                    ->summarize($this->totalizadorResumo('localizado')),

                TextColumn::make('nao_localizado')
                    ->label('Nao Localizado')
                    ->getStateUsing(fn(BemMovel $record): int => $this->resumoDoGrupo($record)['nao_localizado'])
                    ->alignCenter()
                    ->numeric()
                    ->summarize($this->totalizadorResumo('nao_localizado')),

                TextColumn::make('em_transferencia')
                    ->label('Em Transferencia')
                    ->getStateUsing(fn(BemMovel $record): int => $this->resumoDoGrupo($record)['em_transferencia'])
                    ->alignCenter()
                    ->numeric()
                    ->summarize($this->totalizadorResumo('em_transferencia')),

                TextColumn::make('inventariado')
                    ->label('Inventariado')
                    ->getStateUsing(fn(BemMovel $record): int => $this->resumoDoGrupo($record)['inventariado'])
                    ->alignCenter()
                    ->numeric()
                    ->summarize($this->totalizadorResumo('inventariado')),

                TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn(BemMovel $record): int => $this->resumoDoGrupo($record)['total'])
                    ->alignCenter()
                    ->numeric()
                    ->summarize($this->totalizadorResumo('total')),

                TextColumn::make('inicio_atividade')
                    ->label('Inicio')
                    ->alignCenter()
                    ->formatStateUsing(fn(BemMovel $record): string => optional($this->atividadeDoSetor((int) $record->Setor)?->inicio)->format('d/m/Y') ?? '-'),

                TextColumn::make('termino_atividade')
                    ->label('Termino')
                    ->alignCenter()
                    ->formatStateUsing(fn(BemMovel $record): string => optional($this->atividadeDoSetor((int) $record->Setor)?->termino)->format('d/m/Y') ?? '-'),

                TextColumn::make('situacao_atividade')
                    ->label('Situacao')
                    ->alignCenter()
                    ->formatStateUsing(fn(BemMovel $record): string => $this->atividadeDoSetor((int) $record->Setor)?->situacao ?: 'A inventariar')
                    ->badge()
                    ->color(fn(BemMovel $record): string => match (strtolower(trim((string) $this->atividadeDoSetor((int) $record->Setor)?->situacao))) {
                        'finalizado', 'carga efetuada' => 'success',
                        'aberto', 'em andamento' => 'info',
                        default => 'warning',
                    }),
            ])
            ->groups([
                Group::make('Setor')
                    ->label('Unidade / Setor')
                    ->getTitleFromRecordUsing(fn(BemMovel $record): string => $record->setorRef?->rotuloInventario() ?? "Setor {$record->Setor}")
                    ->collapsible(),
            ])
            ->defaultGroup('Setor')
            ->defaultSort('Setor')
            ->defaultKeySort(false)
            ->paginated(false)
            ->emptyStateHeading(
                blank($this->inventarioId)
                    ? 'Selecione um inventario para visualizar o progresso.'
                    : 'Nenhum bem encontrado para este inventario.'
            );
    }

    private function atividadeDoSetor(int $setor): ?AtividadeInventario
    {
        if ($this->atividadesPorSetor === null) {
            $this->atividadesPorSetor = blank($this->inventarioId)
                ? collect()
                : AtividadeInventario::query()
                    ->doInventario($this->inventarioId)
                    ->get()
                    ->keyBy('setor');
        }

        return $this->atividadesPorSetor->get($setor);
    }

    private function totalizadorResumo(string $campo): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(fn(): int => (int) $this->resumosPorGrupo()->sum($campo));
    }

    /**
     * @return array{a_inventariar:int,localizado:int,nao_localizado:int,em_transferencia:int,inventariado:int,total:int}
     */
    private function resumoDoGrupo(BemMovel $record): array
    {
        return $this->resumosPorGrupo()->get($this->grupoKey((int) $record->Setor, $record->ComplementoSetor), [
            'a_inventariar' => 0,
            'localizado' => 0,
            'nao_localizado' => 0,
            'em_transferencia' => 0,
            'inventariado' => 0,
            'total' => 0,
        ]);
    }

    private function resumosPorGrupo(): Collection
    {
        if ($this->resumosPorGrupo !== null) {
            return $this->resumosPorGrupo;
        }

        if (blank($this->inventarioId)) {
            return $this->resumosPorGrupo = collect();
        }

        return $this->resumosPorGrupo = BemMovel::query()
            ->where('id_inventario', $this->inventarioId)
            ->whereIn('SituacaoBem', self::SITUACOES_ATIVAS)
            ->get(['id', 'Setor', 'ComplementoSetor', 'sit_inventario', 'SituacaoBem'])
            ->groupBy(fn(BemMovel $bem): string => $this->grupoKey((int) $bem->Setor, $bem->ComplementoSetor))
            ->map(function (Collection $bens): array {
                /** @var BemMovel $representante */
                $representante = $bens->first();

                return [
                    'id' => (int) $representante->id,
                    'a_inventariar' => $bens->filter(fn(BemMovel $bem): bool => in_array($this->normalizarSituacaoInventario($bem->sit_inventario), ['', 'A INVENTARIAR'], true))->count(),
                    'localizado' => $bens->filter(fn(BemMovel $bem): bool => $this->normalizarSituacaoInventario($bem->sit_inventario) === 'LOCALIZADO')->count(),
                    'nao_localizado' => $bens->filter(fn(BemMovel $bem): bool => in_array($this->normalizarSituacaoInventario($bem->sit_inventario), ['NAO LOCALIZADO', 'NÃO LOCALIZADO'], true))->count(),
                    'em_transferencia' => $bens->filter(fn(BemMovel $bem): bool => str_starts_with($this->normalizarSituacaoInventario($bem->sit_inventario), 'EM TRANSF'))->count(),
                    'inventariado' => $bens->filter(fn(BemMovel $bem): bool => in_array($this->normalizarSituacaoInventario($bem->sit_inventario), ['LOCALIZADO', 'EM TRANSFERENCIA', 'EM TRANSFERÊNCIA'], true))->count(),
                    'total' => $bens->count(),
                ];
            });
    }

    private function normalizarSituacaoInventario(mixed $situacao): string
    {
        return mb_strtoupper(trim((string) $situacao), 'UTF-8');
    }

    private function grupoKey(int $setor, mixed $complementoSetor): string
    {
        return $setor . '-' . (int) ($complementoSetor ?: 0);
    }

    private function getQuery(): Builder
    {
        $idsRepresentantes = $this->resumosPorGrupo()
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if ($idsRepresentantes === []) {
            return BemMovel::query()->whereKey(-1);
        }

        return BemMovel::query()
            ->with(['setorRef', 'complementoSetorRef'])
            ->whereKey($idsRepresentantes);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
