<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Components;

use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Views\MaterialDepositoView;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class MateriaisDisponiveisTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var array<int> */
    public array $selectedPatrimonios = [];

    public ?int $selectedItemPedidoId = null;

    public ?int $selectedMaterialId = null;

    public ?string $selectedMaterialDescricao = null;

    public ?string $selectedMaterialResumo = null;

    public ?string $selectedMaterialPrimeiraPalavra = null;

    #[On('pedido-selecionado')]
    public function onPedidoSelecionado(
        int $pedidoId,
        int $itemPedidoId,
        string $protocolo = '-',
        string $solicitante = '-',
        string $destino = '-',
        string $material = '-',
        int $materialId = 0,
        string $materialResumo = '-',
        string $situacao = '-',
        int $quantidadeSolicitada = 0,
        int $quantidadeValidada = 0,
        int $quantidadeAtendida = 0,
    ): void {
        $this->selectedItemPedidoId = $itemPedidoId > 0 ? $itemPedidoId : null;
        $this->selectedMaterialId = $materialId > 0 ? $materialId : null;
        $this->selectedMaterialDescricao = $this->selectedItemPedidoId && filled($material) && $material !== '-'
            ? $material
            : null;

        $materialResumoSelecionado = $this->selectedItemPedidoId && filled($materialResumo) && $materialResumo !== '-'
            ? $materialResumo
            : null;

        $this->selectedMaterialResumo = $this->selectedItemPedidoId && filled($materialResumo) && $materialResumo !== '-'
            ? $materialResumo
            : null;
        $this->selectedMaterialPrimeiraPalavra = $this->resolvePrimeiraPalavra($materialResumoSelecionado);
    }

    #[On('limpar-selecao-materiais')]
    public function limparSelecaoMateriais(): void
    {
        $this->selectedPatrimonios = [];
    }

    #[On('forcar-remocao-patrimonio')]
    public function forcarRemocaoPatrimonio(int $patrimonioId): void
    {
        $this->selectedPatrimonios = array_values(array_filter(
            $this->selectedPatrimonios,
            fn(int $id): bool => $id !== $patrimonioId
        ));
    }

    #[On('refresh-materiais-table')]
    public function refreshMateriaisTable(): void
    {
        // Apenas força o rerender.
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->heading('Materiais disponíveis')
            ->query($this->getMateriaisQuery())
            ->defaultSort('descricao_resumida', 'asc')
            ->recordClasses(function (MaterialDepositoView $record): string {
                return in_array((int) $record->patrimonio_id, $this->selectedPatrimonios, true)
                    ? 'bg-primary-50 dark:bg-primary-900/20'
                    : '';
            })
            ->columns([
                TextColumn::make('patrimonio_id')
                    ->label('Patrimônio')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('descricao_resumida')
                    ->label('Material')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('descricao_detalhada')
                    ->label('Descrição detalhada')
                    ->wrap(),

                TextColumn::make('complementosetor')
                    ->label('Complemento')
                    ->wrap(),

                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('alternar')
                    ->label(
                        fn(MaterialDepositoView $record): string => in_array((int) $record->patrimonio_id, $this->selectedPatrimonios, true)
                            ? 'Remover'
                            : 'Selecionar'
                    )
                    ->icon('heroicon-o-hand-raised')
                    ->color('gray')
                    ->action(function (MaterialDepositoView $record): void {
                        $id = (int) $record->patrimonio_id;

                        if (in_array($id, $this->selectedPatrimonios, true)) {
                            $this->selectedPatrimonios = array_values(array_filter(
                                $this->selectedPatrimonios,
                                fn(int $selectedId): bool => $selectedId !== $id
                            ));
                        } else {
                            $this->selectedPatrimonios[] = $id;
                        }

                        $this->dispatch('patrimonio-alternado', patrimonioId: $id);
                    }),
            ])
            ->toolbarActions([]);
    }

    protected function getMateriaisQuery(): Builder
    {
        $query = MaterialDepositoView::query();

        if (! $this->selectedItemPedidoId || blank($this->selectedMaterialPrimeiraPalavra)) {
            return $query->whereKey(-1);
        }

        return $query->where(function (Builder $query): void {
            $query
                ->where('descricao_resumida', $this->selectedMaterialPrimeiraPalavra)
                ->orWhere('descricao_resumida', 'like', "{$this->selectedMaterialPrimeiraPalavra} %");
        });
    }

    protected function resolvePrimeiraPalavra(?string $descricao): ?string
    {
        if (blank($descricao)) {
            return null;
        }

        $descricao = trim($descricao);

        if ($descricao === '') {
            return null;
        }

        $partes = preg_split('/\s+/u', $descricao);
        $primeiraPalavra = $partes[0] ?? null;

        return filled($primeiraPalavra)
            ? mb_strtoupper($primeiraPalavra, 'UTF-8')
            : null;
    }

    public function render(): View
    {
        return view('egap.livewire.atendimento-pedidos.materiais-disponiveis-table');
    }
}
