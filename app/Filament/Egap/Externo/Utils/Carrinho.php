<?php

namespace App\Filament\Egap\Externo\Utils;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\CarrinhoMateriaisConsumoForm;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\CarrinhoMateriaisPermanentesForm;
use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component;
use Throwable;

/**
 * Base do carrinho/formulário de envio de pedido do Ambiente Externo,
 * compartilhada entre {@see CarrinhoMateriaisConsumoForm}
 * e {@see CarrinhoMateriaisPermanentesForm}.
 *
 * Guarda o que é idêntico nos dois: os campos "Dados do pedido"
 * (Solicitante/Unidade Judiciária/Setor/Complemento do Setor), o subtotal e a
 * notificação/limpeza ao final do envio. Como o pedido é efetivamente
 * enviado — e como o item é removido do carrinho — fica a cargo de cada
 * subclasse, pois divergem de verdade (ver docblocks das subclasses).
 */
abstract class Carrinho extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $carrinho = [];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getDefaultFormState());
    }

    abstract public function removerItem(int $chave): void;

    abstract public function enviarPedido(): void;

    public function limparCarrinho(): void
    {
        $this->carrinho = [];
    }

    public function getSubtotalCarrinhoProperty(): float
    {
        return collect($this->carrinho)
            ->sum(fn(array $item): float => $item['quantidade'] * $item['preco_unitario']);
    }

    /**
     * Campos comuns do destino do pedido: Solicitante, Unidade Judiciária,
     * Setor e Complemento do Setor de destino.
     *
     * Solicitante nunca é escolhido: vem de {@see EgapSession} no momento da
     * gravação ({@see destinoDoPedido()}), server-side. Unidade e Setor vêm da
     * lotação ativa (o seletor do topo), e só voltam a ser editáveis quando a
     * lotação não resolve — usuário sem lotação, ou lotação cuja unidade não é
     * inventariável (28 dos 886 setores).
     */
    protected function camposDestinoSchema(): array
    {
        $rotulos = EgapSession::rotulosLotacaoAtual();

        return [
            Select::make('ComplementoSetor')
                ->label('Complemento do setor')
                ->required()
                ->searchable()
                ->preload()
                ->native(false)
                ->options(function (Get $get): array {
                    $setorId = $this->setorDoDestino($get);

                    $usados = filled($setorId)
                        ? Pedidos::query()
                        ->where('Setor', $setorId)
                        ->whereNotNull('ComplementoSetor')
                        ->distinct()
                        ->pluck('ComplementoSetor')
                        : collect();

                    return ComplementoSetor::query()
                        ->when($usados->isNotEmpty(), fn($query) => $query->whereIn('id', $usados))
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->toArray();
                })
                ->disabled(fn(Get $get): bool => blank($this->setorDoDestino($get)))
                ->columnSpanFull(),
        ];
    }

    /**
     * O destino do pedido é ditado pela lotação, ou ainda depende dos selects?
     */
    protected function destinoVemDaLotacao(): bool
    {
        return EgapSession::lotacaoUtilizavelComoDestino();
    }

    /**
     * Setor de destino corrente — da lotação quando ela manda, do formulário
     * no caminho de exceção. Usado pelo Complemento do Setor, que depende dele.
     */
    protected function setorDoDestino(Get $get): mixed
    {
        return $this->destinoVemDaLotacao()
            ? EgapSession::setorAtual()
            : $get('Setor');
    }

    protected function destinoDoPedido(array $data): array
    {
        $daLotacao = $this->destinoVemDaLotacao();

        return [
            'Solicitante' => EgapSession::resolverAtual(),
            'UnidadeJudiciaria' => $daLotacao ? EgapSession::unidadeAtual() : ($data['UnidadeJudiciaria'] ?? null),
            'Setor' => $daLotacao ? EgapSession::setorAtual() : ($data['Setor'] ?? null),
            'ComplementoSetor' => $data['ComplementoSetor'] ?? null,
        ];
    }

    protected function getDefaultFormState(): array
    {
        return [
            'UnidadeJudiciaria' => null,
            'Setor' => null,
            'ComplementoSetor' => null,
        ];
    }

    protected function finalizarEnvioComSucesso(int $pedidoId, string $destinatario): void
    {
        Notification::make()
            ->title("Pedido #{$pedidoId} criado com sucesso.")
            ->body("O pedido e os itens foram enviados {$destinatario}.")
            ->success()
            ->send();

        $this->limparCarrinho();
        $this->form->fill($this->getDefaultFormState());

        $this->dispatch('pedido-enviado');
    }

    protected function notificarErro(Throwable $exception): void
    {
        report($exception);

        Notification::make()
            ->title('Erro ao enviar pedido.')
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }
}
