@php
    $numero = str_pad((string) $pedido->id, 7, '0', STR_PAD_LEFT);
    $ano = $pedido->date_time?->format('Y') ?? '----';
    $dataPedido = $pedido->date_time?->format('d/m/Y \à\s H:i') ?? '-';
    $dataAtendimento = $pedido->DataTermino?->format('d/m/Y \à\s H:i') ?? '-';
    $solicitante = $pedido->solicitante_get?->name ?? 'Não informado';
    $setor = $pedido->setor_get?->setor ?? 'Não informado';
    $complementoSetor = $pedido->complementoSetor?->descricao ?? 'Não informado';
    $atendidoPor = $pedido->responsavel_atendimento?->name ?? 'Não informado';
    $observacao = filled($pedido->Observacao) ? $pedido->Observacao : 'Sem observações';
    $protocolo = filled($pedido->num_protocolo) ? $pedido->num_protocolo : 'Não informado';
    $total = 0.0;
@endphp

<x-impressao::documento
    titulo="Requisição de material em estoque"
    :titulo-aba="'Pedido ' . $numero"
    secao="Seção de Material de Consumo"
    kicker="RME - Atendimento"
    :identificador="'Pedido ' . $numero . '/' . $ano"
    :data-emissao="now()->format('d/m/Y H:i')"
    :imprimir-ao-carregar="false"
>
    <h2 class="bloco">Dados do pedido</h2>

    <dl class="doc-campos">
        <div class="doc-campo">
            <dt>Protocolo</dt>
            <dd>{{ $protocolo }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Data do pedido</dt>
            <dd>{{ $dataPedido }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Solicitante</dt>
            <dd>{{ $solicitante }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Setor</dt>
            <dd>{{ $setor }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Complemento</dt>
            <dd>{{ $complementoSetor }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Atendimento</dt>
            <dd>{{ $atendidoPor }} · {{ $dataAtendimento }}</dd>
        </div>
        <div class="doc-campo largo">
            <dt>Observações</dt>
            <dd>{{ $observacao }}</dd>
        </div>
    </dl>

    <h2 class="bloco">Materiais atendidos</h2>

    <div class="doc-table-wrap">
        <table class="rel confortavel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 44px;">Item</th>
                <th class="a-left" scope="col">Descrição</th>
                <th class="a-center" scope="col" style="width: 82px;">Solicitada</th>
                <th class="a-center" scope="col" style="width: 82px;">Atendida</th>
                <th class="a-right" scope="col" style="width: 104px;">Valor médio</th>
                <th class="a-right" scope="col" style="width: 104px;">Valor total</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($pedido->itens as $item)
                @php
                    $descricao = $item->descricaoDetalhadaRef?->descricao_detalhada
                        ?? $item->materialRef?->Descricao
                        ?? 'Descrição não informada';
                    $quantidadeSolicitada = $item->QuantidadeMaterial ?? 0;
                    $quantidadeAtendida = $item->QuantidadeMaterialAtendida ?? 0;
                    $valorMedio = \App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource::normalizarValorMonetario(
                        $item->valor_material,
                    );
                    $valorTotal = $quantidadeAtendida * $valorMedio;
                    $total += $valorTotal;
                @endphp

                <tr>
                    <td class="a-center">{{ $loop->iteration }}</td>
                    <td>{{ $descricao }}</td>
                    <td class="a-center">{{ $quantidadeSolicitada }}</td>
                    <td class="a-center">{{ $quantidadeAtendida }}</td>
                    <td class="a-right nowrap">R$ {{ number_format($valorMedio, 2, ',', '.') }}</td>
                    <td class="a-right nowrap">R$ {{ number_format($valorTotal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="6">Nenhum material vinculado a este pedido.</td>
                </tr>
            @endforelse

            <tr class="total">
                <td class="a-right" colspan="5">TOTAL DO PEDIDO</td>
                <td class="a-right nowrap">R$ {{ number_format($total, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <h2 class="bloco" style="margin-top: 32px;">Conferência e recebimento</h2>

    <div class="doc-assinaturas">
        <div class="doc-assinatura">
            <div class="doc-assinatura-linha">
                <strong>Material separado por</strong>
                <span>Almoxarife - assinatura e carimbo</span>
            </div>
        </div>
        <div class="doc-assinatura">
            <div class="doc-assinatura-linha">
                <strong>Material recebido por</strong>
                <span>Assinatura e carimbo · Data: ____/____/______</span>
            </div>
        </div>
    </div>

    <p class="doc-nota" style="margin-top: 30px;">
        Confira os materiais no ato da entrega. A assinatura confirma o recebimento dos itens e das quantidades relacionados neste documento.
    </p>
</x-impressao::documento>
