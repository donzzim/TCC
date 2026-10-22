<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages;

use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoPage;
use App\Models\egap\Agendamento\Materiais;
use App\Models\egap\Agendamento\Regiao;
use App\Models\egap\Agendamento\Solicitacao;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use App\Models\egap\Views\MaterialDepositoView;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use RuntimeException;
use Throwable;

class AtendimentoPedidosPage extends PedidoPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';

    protected static ?string $title = 'Atendimento de Pedidos';

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Atendimento de Pedidos';

    protected static ?string $slug = 'atendimento-pedidos';

    protected string $view = 'egap.filament.pages.pedidos.atendimento-pedidos';

    public ?int $selectedPedidoId = null;
    public ?int $selectedItemPedidoId = null;
    public ?int $selectedMaterialId = null;

    public ?string $protocolo = null;
    public ?string $solicitante = null;
    public ?string $destino = null;
    public ?string $materialPedido = null;
    public ?string $situacaoPedido = null;

    public int $quantidadeSolicitada = 0;
    public int $quantidadeValidada = 0;
    public int $quantidadeAtendida = 0;
    public int $quantidadePendente = 0;

    /** @var array<int> */
    public array $selectedPatrimonios = [];

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

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
        if ($itemPedidoId <= 0) {
            $this->reset([
                'selectedPedidoId',
                'selectedItemPedidoId',
                'selectedMaterialId',
                'protocolo',
                'solicitante',
                'destino',
                'materialPedido',
                'situacaoPedido',
                'quantidadeSolicitada',
                'quantidadeValidada',
                'quantidadeAtendida',
                'quantidadePendente',
                'selectedPatrimonios',
            ]);

            $this->dispatch('limpar-selecao-materiais');

            return;
        }

        $this->selectedPedidoId = $pedidoId;
        $this->selectedItemPedidoId = $itemPedidoId;
        $this->selectedMaterialId = $materialId > 0 ? $materialId : null;
        $this->protocolo = $protocolo;
        $this->solicitante = $solicitante;
        $this->destino = $destino;
        $this->materialPedido = $material;
        $this->situacaoPedido = $situacao;

        $this->quantidadeSolicitada = $quantidadeSolicitada;
        $this->quantidadeValidada = $quantidadeValidada;
        $this->quantidadeAtendida = $quantidadeAtendida;

        $this->quantidadePendente = $this->quantidadeValidada > 0
            ? max($this->quantidadeValidada - $this->quantidadeAtendida, 0)
            : max($this->quantidadeSolicitada - $this->quantidadeAtendida, 0);

        $this->selectedPatrimonios = [];

        $this->dispatch('limpar-selecao-materiais');
    }

    #[On('patrimonio-alternado')]
    public function onPatrimonioAlternado(int $patrimonioId): void
    {
        if (! $this->selectedItemPedidoId) {
            Notification::make()
                ->title('Selecione um pedido antes de marcar materiais.')
                ->warning()
                ->send();

            $this->dispatch('forcar-remocao-patrimonio', patrimonioId: $patrimonioId);

            return;
        }

        $jaSelecionado = in_array($patrimonioId, $this->selectedPatrimonios, true);

        if ($jaSelecionado) {
            $this->selectedPatrimonios = array_values(array_filter(
                $this->selectedPatrimonios,
                fn(int $id): bool => $id !== $patrimonioId
            ));

            return;
        }

        if (count($this->selectedPatrimonios) >= $this->quantidadePendente) {
            Notification::make()
                ->title('Quantidade selecionada maior que a pendente.')
                ->body('Remova um patrimônio já marcado ou selecione outro pedido.')
                ->danger()
                ->send();

            $this->dispatch('forcar-remocao-patrimonio', patrimonioId: $patrimonioId);

            return;
        }

        $this->selectedPatrimonios[] = $patrimonioId;
    }

    public function getQuantidadeSelecionadaProperty(): int
    {
        return count($this->selectedPatrimonios);
    }

    public function getPodeAtenderProperty(): bool
    {
        return filled($this->selectedPedidoId)
            && filled($this->selectedItemPedidoId)
            && $this->quantidadePendente > 0
            && $this->quantidadeSelecionada === $this->quantidadePendente;
    }

    public function limparSelecao(): void
    {
        $this->reset([
            'selectedPedidoId',
            'selectedItemPedidoId',
            'selectedMaterialId',
            'protocolo',
            'solicitante',
            'destino',
            'materialPedido',
            'situacaoPedido',
            'quantidadeSolicitada',
            'quantidadeValidada',
            'quantidadeAtendida',
            'quantidadePendente',
            'selectedPatrimonios',
        ]);

        $this->dispatch('limpar-selecao-pedidos');
        $this->dispatch('limpar-selecao-materiais');
    }

    public function atenderPedido(): void
    {
        if (! $this->podeAtender) {
            Notification::make()
                ->title('Não é possível atender o pedido.')
                ->body('Confira o pedido selecionado e a quantidade de patrimônios marcados.')
                ->danger()
                ->send();

            return;
        }

        try {
            $resultado = DB::connection('egap')->transaction(function () {
                $userId = auth()->id();

                if (! $userId) {
                    throw new RuntimeException('Usuário autenticado não encontrado.');
                }

                /** @var Pedidos $pedido */
                $pedido = Pedidos::query()->findOrFail($this->selectedPedidoId);

                /** @var ItemPedido $itemPedido */
                $itemPedido = ItemPedido::query()
                    ->with(['idPedidoRef', 'materialRef', 'descricaoDetalhadaRef.descricao_resumida_text'])
                    ->findOrFail($this->selectedItemPedidoId);

                $quantidadeSelecionada = count($this->selectedPatrimonios);

                $quantidadeSolicitada = (int) ($itemPedido->QuantidadeMaterial ?? 0);
                $quantidadeValidada = (int) ($itemPedido->quantidade_validada ?? 0);
                $quantidadeAtendidaAtual = (int) ($itemPedido->QuantidadeMaterialAtendida ?? 0);

                $quantidadePendente = $quantidadeValidada > 0
                    ? max($quantidadeValidada - $quantidadeAtendidaAtual, 0)
                    : max($quantidadeSolicitada - $quantidadeAtendidaAtual, 0);

                if ($quantidadeSelecionada <= 0) {
                    throw new RuntimeException('Nenhum patrimônio foi selecionado para o atendimento.');
                }

                if ($quantidadeSelecionada !== $quantidadePendente) {
                    throw new RuntimeException('A quantidade de patrimônios selecionados deve ser igual à quantidade pendente do pedido.');
                }

                $materialPrimeiraPalavra = $this->resolveMaterialPrimeiraPalavra($itemPedido);

                if (blank($materialPrimeiraPalavra)) {
                    throw new RuntimeException('Nao foi possivel identificar a primeira palavra da descricao resumida do item do pedido.');
                }

                $materialResumoId = $this->resolveMaterialResumoId($itemPedido);

                if ($materialResumoId <= 0) {
                    throw new RuntimeException('Não foi possível identificar a descrição resumida do item do pedido.');
                }

                $materiaisCompativeis = MaterialDepositoView::query()
                    ->whereIn('patrimonio_id', $this->selectedPatrimonios)
                    ->where(function (QueryBuilder $query) use ($materialPrimeiraPalavra): void {
                        $query
                            ->where('descricao_resumida', $materialPrimeiraPalavra)
                            ->orWhere('descricao_resumida', 'like', "{$materialPrimeiraPalavra} %");
                    })
                    ->count();

                if ($materiaisCompativeis !== $quantidadeSelecionada) {
                    throw new RuntimeException('Os patrimônios selecionados não correspondem à mesma descrição resumida do item do pedido ou não estão mais disponíveis no depósito.');
                }

                $bens = BemMovel::query()
                    ->whereIn('id', $this->selectedPatrimonios)
                    ->get();

                if ($bens->count() !== $quantidadeSelecionada) {
                    throw new RuntimeException('Um ou mais patrimônios selecionados não foram encontrados.');
                }

                $anoAtual = now()->year;

                $ultimoNumeroTermo = Termo::query()
                    ->where('ano_termo', $anoAtual)
                    ->max('num_termo');

                $novoNumeroTermo = ((int) $ultimoNumeroTermo) + 1;

                $termo = Termo::query()->create([
                    'date_time' => now(),
                    'num_termo' => $novoNumeroTermo,
                    'ano_termo' => $anoAtual,
                    'atualizado_em' => now(),
                    'atualizado_por' => $userId,
                    'pedido_no' => $pedido->id,
                    'situacao_entrega' => 'Encaminhado para Logística',
                ]);

                ArquivoDigital::query()->create([
                    'date_time' => now(),
                    'termo' => $termo->id,
                    'atualizado_em' => now(),
                    'atualizado_por' => $userId,
                ]);

                foreach ($bens as $bem) {
                    $ultimaTransferencia = TransferenciaBemMovel::query()
                        ->where('NumPatrimonio', $bem->id)
                        ->latest('id')
                        ->first();

                    $bem->date_time = now();
                    $bem->UnidadeJudiciaria = $pedido->UnidadeJudiciaria;
                    $bem->Setor = $pedido->Setor;
                    $bem->ComplementoSetor = $pedido->ComplementoSetor;
                    $bem->Usuario = $userId;
                    $bem->DataDisponibilizacao = $bem->DataDisponibilizacao ?: now();
                    $bem->save();

                    TransferenciaBemMovel::query()->create([
                        'date_time' => now(),
                        'NumPatrimonio' => $bem->id,
                        'UnidadeAtual' => $pedido->UnidadeJudiciaria,
                        'SetorAtual' => $pedido->Setor,
                        'ComplementoAtual' => $pedido->ComplementoSetor,
                        'Usuario' => $userId,
                        'Termo' => $termo->id,
                        'pedido_no' => $pedido->id,
                        'UnidadeAnterior' => $ultimaTransferencia?->UnidadeAtual,
                        'SetorAnterior' => $ultimaTransferencia?->SetorAtual,
                        'ComplementoAnterior' => $ultimaTransferencia?->ComplementoAtual,
                    ]);
                }

                $itemPedido->situacao = 9;
                $itemPedido->QuantidadeMaterialAtendida = $quantidadeAtendidaAtual + $quantidadeSelecionada;
                $itemPedido->save();

                $regiao = Regiao::query()
                    ->where('unidade', $pedido->UnidadeJudiciaria)
                    ->first();

                $setor = Setores::query()->find($pedido->Setor);

                $solicitacao = Solicitacao::query()->create([
                    'date_time' => now(),
                    'id_user' => $userId,
                    'tipo' => 2,
                    'id_situacao' => 6,
                    'id_solicitante' => $termo->atualizado_por,
                    'unidade_solicitante' => $pedido->UnidadeJudiciaria,
                    'setor_solicitante' => $pedido->Setor,
                    'regiao' => $regiao?->id,
                    'justificativa' => json_encode([
                        'justificativa' => 'Solicito entrega dos materiais permanentes conforme Termo de Responsabilidade.',
                    ], JSON_UNESCAPED_UNICODE),
                    'local_saida' => 'Seção de Patrimônio',
                    'local_destino' => $setor?->setor,
                ]);

                Materiais::query()->create([
                    'date_time' => now(),
                    'id_pedido' => $pedido->id,
                    'id_termo' => $termo->id,
                    'id_user' => $userId,
                    'id_solicitacao' => $solicitacao->id,
                ]);

                FasePedido::query()->create([
                    'idSituacao' => 9,
                    'Descricao' => "Pedido encaminhado para logistica pelo atendimento do item #{$itemPedido->id}.",
                    'id_pedido' => $pedido->id,
                    'quantidade' => $quantidadeSelecionada,
                ]);

                FasePedido::query()->create([
                    'idSituacao' => 9,
                    'Descricao' => "Item encaminhado para logistica com o termo #{$termo->num_termo}/{$termo->ano_termo}.",
                    'id_pedido' => $pedido->id,
                    'id_itempedido' => $itemPedido->id,
                    'id_descricaoresumida' => $materialResumoId,
                    'id_descricaodetalhada' => $itemPedido->DescricaoDetalhada,
                    'quantidade' => $quantidadeSelecionada,
                ]);

                return [
                    'termo_id' => $termo->id,
                    'num_termo' => $termo->num_termo,
                    'solicitacao_id' => $solicitacao->id,
                ];
            });

            Notification::make()
                ->title('Pedido atendido com sucesso.')
                ->body("Termo nº {$resultado['num_termo']} gerado com sucesso.")
                ->success()
                ->send();

            $this->limparSelecao();

            $this->dispatch('refresh-pedidos-table');
            $this->dispatch('refresh-materiais-table');
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Erro ao atender pedido.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function resolveMaterialResumoId(ItemPedido $itemPedido): int
    {
        $materialId = (int) ($itemPedido->material ?? 0);

        if ($materialId > 0) {
            return $materialId;
        }

        return (int) ($itemPedido->descricaoDetalhadaRef?->descricao_resumida ?? 0);
    }

    protected function resolveMaterialPrimeiraPalavra(ItemPedido $itemPedido): ?string
    {
        $descricaoResumida = $itemPedido->descricaoDetalhadaRef?->descricao_resumida_text?->Descricao
            ?? $itemPedido->materialRef?->Descricao;

        if (blank($descricaoResumida)) {
            return null;
        }

        $descricaoResumida = trim($descricaoResumida);

        if ($descricaoResumida === '') {
            return null;
        }

        $partes = preg_split('/\s+/u', $descricaoResumida);
        $primeiraPalavra = $partes[0] ?? null;

        return filled($primeiraPalavra)
            ? mb_strtoupper($primeiraPalavra, 'UTF-8')
            : null;
    }
}
