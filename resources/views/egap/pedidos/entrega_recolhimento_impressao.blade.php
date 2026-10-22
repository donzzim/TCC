@php
    use App\Filament\Egap\Interno\Groups\Pedidos\Components\EntregaRecolhimentoTable;
    use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;

    $total = $termos->count();
@endphp

<x-impressao::documento
    titulo="Agendamento de Entrega e Recolhimento"
    secao="Seção de Patrimônio"
    kicker="Termos pendentes de agendamento"
    orientacao="paisagem"
    :identificador="$total . ' ' . ($total === 1 ? 'termo' : 'termos')"
    :data-emissao="now()->format('d/m/Y H:i')"
    rodape="e-GAPes · Agendamento de Entrega e Recolhimento"
>
    <div class="doc-table-wrap">
        <table class="rel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 84px;">Tipo</th>
                <th class="a-center" scope="col">Termo</th>
                <th class="a-left" scope="col">Origem</th>
                <th class="a-left" scope="col">Destino</th>
                <th class="a-left" scope="col">Atualizado por</th>
                <th class="a-center" scope="col">Situação do termo</th>
                <th class="a-left" scope="col">Observação</th>
                <th class="a-center" scope="col" style="width: 58px;">Pedido</th>
                <th class="a-center" scope="col">Situação da solicitação</th>
                <th class="a-center" scope="col">Situação da entrega</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($termos as $termo)
                @php
                    $transferencia = $termo->ultimaTransferencia;
                @endphp
                <tr>
                    <td class="a-center forte nowrap">{{ EntregaRecolhimentoTable::tipo($termo) }}</td>
                    <td class="a-center nowrap">{{ $termo->termo_completo }}</td>
                    <td>
                        {{ $transferencia?->setorAnteriorRel?->setor ?? '-' }}
                        @if ($complemento = $transferencia?->complementoAnteriorRel?->descricao)
                            <br><span style="color: var(--muted);">{{ $complemento }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $transferencia?->setorAtualRel?->setor ?? '-' }}
                        @if ($complemento = $transferencia?->complementoAtualRel?->descricao)
                            <br><span style="color: var(--muted);">{{ $complemento }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $transferencia?->usuarioRef?->name ?? '-' }}
                        @if ($transferencia?->date_time)
                            <br><span style="color: var(--muted);">{{ $transferencia->date_time->format('d/m/Y H:i') }}</span>
                        @endif
                    </td>
                    <td class="a-center nowrap">{{ ArquivoDigital::situacaoLabel($termo->arquivoDigital?->situacao) }}</td>
                    <td>{{ $termo->arquivoDigital?->observacao ?: '-' }}</td>
                    <td class="a-center nowrap">{{ $termo->pedido_no ?: '-' }}</td>
                    <td class="a-center nowrap">{{ $termo->ultimoMaterialTransporte?->idSolicitacaoRef?->idSituacaoRef?->Descricao ?? 'Não encaminhado' }}</td>
                    <td class="a-center nowrap">{{ $termo->situacao_entrega ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="10">Nenhum termo pendente de agendamento.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-impressao::documento>
