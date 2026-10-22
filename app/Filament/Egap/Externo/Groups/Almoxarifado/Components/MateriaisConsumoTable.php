<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Components;

use App\Filament\Egap\Externo\Utils\MateriaisDisponiveis;
use App\Filament\Egap\Helper\ImagemPathHelper;
use App\Filament\Egap\Support\ExpandableImageColumn;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class MateriaisConsumoTable extends MateriaisDisponiveis implements HasActions
{
    use InteractsWithActions;
    public string $tipoMaterial;

    /** @var array<int, MovimentacaoEstoque|null> última movimentação de estoque por material, indexada pelo id do material */
    protected array $estoquesAtuaisPorMaterial = [];

    public function mount(string $tipoMaterial): void
    {
        $this->tipoMaterial = $tipoMaterial;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->recordClasses(fn(DescricaoDetalhada $record): ?string => $this->emEstoque($record) ? null : 'opacity-50 grayscale')
            ->columns([
                TableColumns::text('descricao_detalhada', 'Material', isFirstColumn: true)
                    ->description(fn(DescricaoDetalhada $record): ?string => $this->emEstoque($record) ? null : 'Material indisponível no momento')
                    ->wrap(),

                TableColumns::text('unidadeMedida.Sigla', 'Unidade de Medida')
                    ->sortable(false),

                TableColumns::money('valor_material', 'Preço médio')
                    ->searchable(false)
                    ->badge()
                    ->color('success')
                    ->sortable(false)
                    ->getStateUsing(
                        fn (DescricaoDetalhada $record): ?float =>
                        (float) ($this->estoqueAtual($record)?->preco_medio_estoque ?? 0)
                    ),

                ExpandableImageColumn::make('imagem')
                    ->label('Imagem ilustrativa')
                    ->disk('egap')
                    ->state(fn(DescricaoDetalhada $record): string => $this->imagemPathComFallback($record))
                    ->expandModalHeading(fn(DescricaoDetalhada $record): string => $record->descricao_detalhada),

                $this->quantidadeColumn(),
            ])
            ->recordActions([
                Action::make('adicionar')
                    ->label('Adicionar')
                    ->disabled(
                        fn(DescricaoDetalhada $record): bool =>
                        ! $this->emEstoque($record)
                    )
                    ->tooltip(
                        fn(DescricaoDetalhada $record): ?string =>
                        $this->emEstoque($record)
                            ? null
                            : 'Material indisponível no momento'
                    )
                    ->button()
                    ->icon('heroicon-m-plus')
                    ->action(fn(DescricaoDetalhada $record) => $this->adicionarAoCarrinho($record)),
            ])
            ->toolbarActions([])
            ->defaultSort('descricao_detalhada');
    }

    protected function adicionarAoCarrinho(DescricaoDetalhada $record): void
    {
        if (! $this->emEstoque($record)) {
            Notification::make()
                ->title('Material indisponível')
                ->body('Este material não possui estoque disponível.')
                ->warning()
                ->send();

            return;
        }

        $quantidade = $this->validarQuantidade($record->id);

        if ($quantidade === null) {
            return;
        }

        $this->dispatch(
            'item-adicionado-ao-carrinho',
            materialId: $record->id,
            descricaoResumidaId: (int) $record->descricao_resumida,
            descricao: $record->descricao_detalhada,
            quantidade: $quantidade,
            precoUnitario: (float) ($this->estoqueAtual($record)?->preco_medio_estoque ?? 0),
        );

        Notification::make()
            ->title('Item(ns) adicionado(s) ao carrinho.')
            ->success()
            ->send();

        unset($this->quantidades[$record->id]);
    }

    protected function emEstoque(DescricaoDetalhada $record): bool
    {
        return ((float) ($this->estoqueAtual($record)?->quantidade_estoque ?? 0)) > 0;
    }

    protected function estoqueAtual(DescricaoDetalhada $record): ?MovimentacaoEstoque
    {
        $materialId = (int) $record->getKey();

        if (! array_key_exists($materialId, $this->estoquesAtuaisPorMaterial)) {
            $this->estoquesAtuaisPorMaterial[$materialId] = MovimentacaoEstoque::query()
                ->where('material', $materialId)
                ->latest('id')
                ->first();
        }

        return $this->estoquesAtuaisPorMaterial[$materialId];
    }

    protected function imagemPathComFallback(DescricaoDetalhada $record): string
    {
        return ImagemPathHelper::resolveComFallback($record->imagem);
    }

    protected function getQuery(): Builder
    {
        return DescricaoDetalhada::query()
            ->with('unidadeMedida')
            ->whereIn('visibilidade', $this->visibilidadesPermitidas())
            ->whereHas('descricao_resumida_text', fn(Builder $query) => $query->where('id_tipo_material', $this->tipoMaterial));
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
