<x-impressao::documento
    titulo="Relação de Materiais para Transporte"
    :titulo-aba="'Relação de Materiais - Transporte #' . $solicitacao->id"
    secao="Setor de Agendamento"
    kicker="Agendamento de transporte"
    :identificador="'Transporte Nº ' . $solicitacao->id"
    :data-emissao="$dataEmissao"
    rodape="e-GAPes · Setor de Agendamento"
>
    <p class="doc-declaracao">
        Declaro pelo presente documento de responsabilidade que os materiais especificados abaixo estão devidamente
        embarcados para o transporte solicitado. O transportador se compromete a zelar pelo bom uso e guarda dos
        mesmos até a efetivação da entrega, bem como informar eventual defeito ou avaria, sob pena de responsabilização.
    </p>

    <dl class="doc-campos">
        <div class="doc-campo">
            <dt>Solicitante</dt>
            <dd>{{ mb_strtoupper($solicitacao->idSolicitanteRef?->name ?? 'NÃO INFORMADO') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Pedido Nº</dt>
            <dd>{{ $pedidoProtocolo ?: '-' }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Unidade</dt>
            <dd>{{ mb_strtoupper($unidade) }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Setor</dt>
            <dd>{{ mb_strtoupper($setor) }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Local de saída</dt>
            <dd>{{ mb_strtoupper($solicitacao->local_saida ?: 'NÃO INFORMADO') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Local de destino</dt>
            <dd>{{ mb_strtoupper($solicitacao->local_destino ?: 'NÃO INFORMADO') }}</dd>
        </div>
    </dl>

    <div class="doc-table-wrap">
        <table class="rel confortavel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 42px;">Item</th>
                <th class="a-center" scope="col" style="width: 74px;">Patrimônio</th>
                <th class="a-left" scope="col">Descrição do bem / Marca / Modelo</th>
                <th class="a-center" scope="col" style="width: 90px;">Série</th>
                <th class="a-center" scope="col" style="width: 70px;">Situação</th>
                <th class="a-center" scope="col" style="width: 54px;">Andar</th>
                <th class="a-center" scope="col" style="width: 70px;">Termo</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($itens as $index => $bem)
                <tr>
                    <td class="a-center">{{ $index + 1 }}</td>
                    <td class="a-center forte">{{ $bem->NumPatrimonio }}</td>
                    <td>
                        {{ collect([$bem->Descricao, $bem->marca_desc ? "Marca: {$bem->marca_desc}" : null, $bem->modelo_desc ? "Modelo: {$bem->modelo_desc}" : null])->filter()->implode(", ") }}
                    </td>
                    <td class="a-center">{{ $bem->NumerodeSerie ?: '-' }}</td>
                    <td class="a-center">{{ mb_strtoupper($bem->EstadodeConservacao ?? 'BOM') }}</td>
                    <td class="a-center">{{ $bem->andar_atual ?: '-' }}</td>
                    <td class="a-center">{{ $bem->termo_completo }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="7">Nenhum material vinculado a esta solicitação.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="doc-assinaturas" style="--colunas: 3; gap: 24px;">
        <div class="doc-assinatura">
            <div class="doc-assinatura-caixa"></div>
            <div class="doc-assinatura-linha">
                <strong>Setor (Emitente)</strong>
                <span>Carimbo e Assinatura</span>
            </div>
        </div>
        <div class="doc-assinatura">
            <div class="doc-assinatura-caixa"></div>
            <div class="doc-assinatura-linha">
                <strong>Setor (Destinatário)</strong>
                <span>Carimbo e Assinatura</span>
                <span class="doc-assinatura-data">Recebido em ____/____/______.</span>
            </div>
        </div>
        <div class="doc-assinatura">
            <div class="doc-assinatura-caixa"></div>
            <div class="doc-assinatura-linha">
                <strong>Motorista</strong>
                <span>Assinatura</span>
                <span class="doc-assinatura-data">Embarcado em ____/____/______.</span>
            </div>
        </div>
    </div>

    <p class="doc-nota" style="margin-top: 30px;">
        Os itens constantes nesta relação foram devidamente conferidos e embarcados, sendo sua guarda de
        responsabilidade do transportador até a efetivação da entrega.
    </p>
</x-impressao::documento>
