<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Components;

use App\Filament\Egap\Externo\Utils\Carrinho;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Services\Egap\Mobile\PedidosMobileService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Throwable;

/**
 * Carrinho e envio do pedido de materiais de consumo do Ambiente Externo
 * (legado: pedidos_consumo.php), companheiro de {@see MateriaisConsumoTable}.
 *
 * Grava o pedido direto (transação local com {@see Pedidos}/{@see ItemPedido}/
 * {@see FasePedido}) — ao contrário do fluxo de materiais permanentes, que
 * delega para {@see PedidosMobileService}.
 */
class CarrinhoMateriaisConsumoForm extends Carrinho implements HasActions
{
    use InteractsWithActions;
    protected const STATUS_EM_ANALISE = 6;

    protected const SETOR_ALMOXARIFADO = 799;

    /** @var array<int, array{material_id: int, descricao_resumida_id: int, descricao: string, quantidade: int, preco_unitario: float}> */
    public array $carrinho = [];

    #[On('item-adicionado-ao-carrinho')]
    public function onItemAdicionado(int $materialId, int $descricaoResumidaId, string $descricao, int $quantidade, float $precoUnitario): void
    {
        $this->carrinho[$materialId] = [
            'material_id' => $materialId,
            'descricao_resumida_id' => $descricaoResumidaId,
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'preco_unitario' => $precoUnitario,
        ];
    }

    public function removerItem(int $materialId): void
    {
        if (! isset($this->carrinho[$materialId])) {
            Notification::make()
                ->title('Item não encontrado')
                ->warning()
                ->send();

            return;
        }

        unset($this->carrinho[$materialId]);

        Notification::make()
            ->title('Item removido')
            ->danger()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        ...$this->camposDestinoSchema(),

                        Textarea::make('justificativa')
                            ->label('Justificativa')
                            ->required()
                            ->rows(4)
                            ->maxLength(300)
                            ->placeholder('Descreva a necessidade do pedido.')
                            ->columnSpan(12),
                    ]),
            ])
            ->statePath('data');
    }

    public function enviarPedido(): void
    {
        if ($this->carrinho === []) {
            Notification::make()
                ->title('Adicione ao menos um material ao carrinho antes de enviar.')
                ->danger()
                ->send();

            return;
        }

        if ($this->envioBloqueadoSemLotacao()) {
            return;
        }

        $data = $this->form->getState();
        $destino = $this->destinoDoPedido($data);

        try {
            $pedido = DB::transaction(function () use ($data, $destino): Pedidos {
                $pedido = Pedidos::query()->create([
                    'Solicitante' => $destino['Solicitante'],
                    'UnidadeJudiciaria' => $destino['UnidadeJudiciaria'],
                    'Setor' => $destino['Setor'],
                    'idSituacao' => self::STATUS_EM_ANALISE,
                    'setor_responsavel' => self::SETOR_ALMOXARIFADO,
                    'Observacao' => $data['justificativa'],
                    'ComplementoSetor' => $destino['ComplementoSetor'],
                ]);

                $this->registrarFase($pedido, null, 'Pedido criado via portal externo.');

                foreach ($this->carrinho as $item) {
                    /** @var ItemPedido $itemPedido */
                    $itemPedido = $pedido->itens()->create([
                        'QuantidadeMaterial' => $item['quantidade'],
                        'QuantidadeMaterialAtendida' => 0,
                        'material' => $item['descricao_resumida_id'],
                        'DescricaoDetalhada' => $item['material_id'],
                        'situacao' => self::STATUS_EM_ANALISE,
                        'valor_material' => $item['preco_unitario'],
                    ]);

                    $this->registrarFase($pedido, $itemPedido, 'Item incluído no pedido via portal externo.');
                }

                return $pedido;
            });

            $this->finalizarEnvioComSucesso($pedido->id, 'ao almoxarifado');
        } catch (Throwable $exception) {
            $this->notificarErro($exception);
        }
    }

    protected function registrarFase(Pedidos $pedido, ?ItemPedido $item, string $descricao): void
    {
        FasePedido::query()->create([
            'idSituacao' => self::STATUS_EM_ANALISE,
            'Descricao' => $descricao,
            'id_pedido' => $pedido->id,
            'id_itempedido' => $item?->id,
            'id_descricaoresumida' => $item?->material,
            'id_descricaodetalhada' => $item?->DescricaoDetalhada,
            'quantidade' => $item?->QuantidadeMaterial,
        ]);
    }

    protected function getDefaultFormState(): array
    {
        return [
            ...parent::getDefaultFormState(),
            'justificativa' => null,
        ];
    }

    public function render(): View
    {
        return view('egap.livewire.externo.carrinho-pedido-form');
    }
}
