<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\CarrinhoMateriaisConsumoForm;
use App\Filament\Egap\Externo\Utils\Carrinho;
use App\Services\Egap\Mobile\PedidosMobileService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Throwable;

/**
 * Carrinho e envio do pedido de materiais permanentes do Ambiente Externo
 * (legado: pedidos.php), companheiro de {@see MateriaisPermanentesTable}.
 *
 * "Dados do pedido" segue o mesmo padrão de
 * {@see CarrinhoMateriaisConsumoForm}
 * (Solicitante/Unidade/Setor/Complemento escolhidos manualmente, sem depender
 * de sessão de setor).
 *
 * O pedido em si é criado reaproveitando {@see PedidosMobileService::criarPedido()}:
 * a mesma regra de negócio (formatação da justificativa de adição/substituição,
 * cálculo do valor do bem, registro de fases) já usada pelo aplicativo mobile
 * para o tipo "permanente", evitando duplicar essa lógica aqui.
 */
class CarrinhoMateriaisPermanentesForm extends Carrinho implements HasActions
{
    use InteractsWithActions;
    /** @var array<int, array{material_id: int, descricao: string, quantidade: int, tipo_atendimento: string, patrimonio_substituido: ?string, justificativa: string, preco_unitario: float}> */
    public array $carrinho = [];

    #[On('item-adicionado-ao-carrinho-permanente')]
    public function onItemAdicionado(
        int $materialId,
        string $descricao,
        int $quantidade,
        string $tipoAtendimento,
        ?string $patrimonioSubstituido,
        string $justificativa,
        float $precoUnitario,
    ): void {
        $this->carrinho[] = [
            'material_id' => $materialId,
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'tipo_atendimento' => $tipoAtendimento,
            'patrimonio_substituido' => $patrimonioSubstituido,
            'justificativa' => $justificativa,
            'preco_unitario' => $precoUnitario,
        ];
    }

    public function removerItem(int $index): void
    {
        unset($this->carrinho[$index]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)->schema($this->camposDestinoSchema()),
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

        $data = $this->form->getState();

        $destino = $this->destinoDoPedido($data);

        $scope = [
            'user_id' => auth()->id(),
            'id_egap' => $destino['Solicitante'],
            'setor' => (int) $destino['Setor'],
            'unidade_judiciaria' => (int) $destino['UnidadeJudiciaria'],
        ];

        try {
            $pedido = app(PedidosMobileService::class)->criarPedido($scope, [
                'tipo' => 'permanente',
                'complemento_setor_id' => $destino['ComplementoSetor'],
                'itens' => collect($this->carrinho)
                    ->map(fn(array $item): array => [
                        'material_id' => $item['material_id'],
                        'quantidade' => $item['quantidade'],
                        'tipo_atendimento' => $item['tipo_atendimento'],
                        'justificativa' => $item['justificativa'],
                        'patrimonio_substituido' => $item['patrimonio_substituido'],
                    ])
                    ->all(),
            ]);

            $this->finalizarEnvioComSucesso($pedido->id, 'à Seção de Patrimônio');
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Não foi possível enviar o pedido.')
                ->body((string) collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();
        } catch (Throwable $exception) {
            $this->notificarErro($exception);
        }
    }

    public function render(): View
    {
        return view('egap.livewire.externo.carrinho-pedido-form');
    }
}
