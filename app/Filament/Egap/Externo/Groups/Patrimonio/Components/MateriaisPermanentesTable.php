<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\MateriaisConsumoTable;
use App\Filament\Egap\Externo\Utils\MateriaisDisponiveis;
use App\Filament\Egap\Helper\ImagemPathHelper;
use App\Filament\Egap\Support\ExpandableImageColumn;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lista os materiais permanentes (mat_descricaoresumida.id_tipo_material = 'P')
 * disponíveis para requisição (legado: pedidos.php), companheiro de
 * {@see CarrinhoMateriaisPermanentesForm}, que guarda o carrinho e envia o pedido.
 *
 * Segue o mesmo padrão de
 * {@see MateriaisConsumoTable}:
 * a quantidade é digitada direto na tabela; o botão "Adicionar" só abre um
 * modal para os dados que não cabem em coluna (Tipo de solicitação e
 * Justificativa — e o nº do patrimônio, quando for Substituição).
 */
class MateriaisPermanentesTable extends MateriaisDisponiveis implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('Descricao', 'Material', isFirstColumn: true)
                    ->wrap(),

                TableColumns::money('valor', 'Valor estimado')
                    ->getStateUsing(fn(DescricaoResumida $record): float => $this->valorEstimado($record))
                    ->searchable(false)
                    ->sortable(false),

                ExpandableImageColumn::make('imagem')
                    ->label('Imagem ilustrativa')
                    ->disk('egap')
                    ->state(fn(DescricaoResumida $record): string => $this->imagemPathComFallback($record))
                    ->expandModalHeading(fn(DescricaoResumida $record): string => $record->Descricao),

                $this->quantidadeColumn(),
            ])
            ->recordActions([
                $this->adicionarAction(),
            ])
            ->toolbarActions([])
            ->defaultSort('Descricao')
            ->emptyStateHeading('Nenhum material disponível no momento');
    }

    private function adicionarAction(): Action
    {
        return Action::make('adicionar')
            ->label('Adicionar')
            ->button()
            ->icon('heroicon-m-plus')
            ->modalHeading(fn(DescricaoResumida $record): string => "Adicionar - {$record->Descricao}")
            ->modalSubmitActionLabel('Adicionar ao carrinho')
            ->schema([
                Radio::make('tipo_atendimento')
                    ->label('Tipo de solicitação')
                    ->options([
                        'adicao' => 'Adição',
                        'substituicao' => 'Substituição',
                    ])
                    ->inline()
                    ->required()
                    ->live()
                    ->default('adicao'),

                TextInput::make('patrimonio_substituido')
                    ->label('Nº do(s) patrimônio(s) a substituir')
                    ->placeholder('Ex: 12345, 12346')
                    ->maxLength(120)
                    // ->options(fn() : array => BemMovel::all()->pluck('NumPatrimonio', 'id')->toArray())
                    ->visible(fn(Get $get): bool => $get('tipo_atendimento') === 'substituicao')
                    ->required(fn(Get $get): bool => $get('tipo_atendimento') === 'substituicao'),

                Textarea::make('justificativa')
                    ->label('Justificativa')
                    ->required()
                    ->rows(4)
                    ->maxLength(300)
                    ->placeholder('Descreva a necessidade do material.'),
            ])
            ->action(function (DescricaoResumida $record, array $data): void {
                $quantidade = $this->validarQuantidade($record->id);

                if ($quantidade === null) {
                    return;
                }

                $this->dispatch(
                    'item-adicionado-ao-carrinho-permanente',
                    materialId: $record->id,
                    descricao: $record->Descricao,
                    quantidade: $quantidade,
                    tipoAtendimento: $data['tipo_atendimento'],
                    patrimonioSubstituido: $data['patrimonio_substituido'] ?? null,
                    justificativa: $data['justificativa'],
                    precoUnitario: $this->valorEstimado($record),
                );

                Notification::make()
                    ->title('Item adicionado ao carrinho.')
                    ->success()
                    ->send();

                unset($this->quantidades[$record->id]);
            });
    }

    protected function imagemPathComFallback(DescricaoResumida $record): string
    {
        return ImagemPathHelper::resolveComFallback($record->imagem);
    }

    private function getQuery(): Builder
    {
        return DescricaoResumida::query()
            ->where('id_tipo_material', 'P')
            ->whereIn('visibilidade', $this->visibilidadesPermitidas());
    }

    private function valorEstimado(DescricaoResumida $record): float
    {
        $bem = BemMovel::query()
            ->where('DescricaoResumidadoBem', $record->id)
            ->where('ValorAquisicao', '<>', 0)
            ->where('ValordaReavaliacao', '<>', 0)
            ->orderByDesc('DatadaReavaliacao')
            ->first(['DatadeIncorporacao', 'DatadaReavaliacao', 'ValorAquisicao', 'ValordaReavaliacao']);

        if (! $bem) {
            return 0.0;
        }

        return $bem->DatadeIncorporacao > $bem->DatadaReavaliacao
            ? (float) $bem->ValorAquisicao
            : (float) $bem->ValordaReavaliacao;
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
