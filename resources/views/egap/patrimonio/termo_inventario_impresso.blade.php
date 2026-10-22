@php
    $numeroTermo = "{$termo->num_termo}/{$termo->ano_termo}";
@endphp

<x-impressao::documento
    titulo="Termo de Responsabilidade - Inventário"
    :titulo-aba="'Termo de Inventário ' . $numeroTermo"
    secao="Comissão de Inventário"
    kicker="Inventário de bens patrimoniais"
    :identificador="'Nº ' . $numeroTermo"
    :data-emissao="now()->format('d/m/Y H:i')"
    rodape="e-GAPes · Comissão de Inventário"
    :rodape-direita="'Termo de Inventário Nº ' . $numeroTermo"
>
    <p class="doc-declaracao">
        Declaro pelo presente documento que os materiais especificados abaixo estão devidamente localizados nesta unidade. Comprometo-me a zelar pelo bom uso e guarda dos mesmos, bem como informar a Seção de Patrimônio eventual modificação de sua localização e/ou qualquer defeito/avaria, sob pena de responsabilização.
    </p>

    <dl class="doc-campos" style="--colunas: 3;">
        <div class="doc-campo">
            <dt>Unidade</dt>
            <dd>{{ mb_strtoupper($unidade) }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Setor</dt>
            <dd>{{ mb_strtoupper($setor) }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Complemento</dt>
            <dd>{{ mb_strtoupper($complemento) }}</dd>
        </div>
    </dl>

    <div class="doc-table-wrap">
        <table class="rel confortavel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 40px;">Item</th>
                <th class="a-center" scope="col" style="width: 90px;">Patrimônio</th>
                <th class="a-left" scope="col">Descrição do bem / Marca / Modelo</th>
                <th class="a-center" scope="col" style="width: 90px;">Situação</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($itens as $index => $item)
                <tr>
                    <td class="a-center">{{ $index + 1 }}</td>
                    <td class="a-center forte">{{ $item->num_patrimonio }}</td>
                    <td>
                        {{ collect([$item->descricao_detalhada, $item->marca ? "Marca: {$item->marca}" : null, $item->modelo ? "Modelo: {$item->modelo}" : null])->filter()->implode(" - ") }}
                    </td>
                    <td class="a-center">{{ mb_strtoupper($item->estado_conservacao ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="4">Nenhum bem inventariado neste termo.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="doc-assinaturas">
        <div class="doc-assinatura">
            <div class="doc-assinatura-linha">
                <strong>Comissão (Membro)</strong>
                <span>{{ mb_strtoupper($responsavel) }}</span>
                <span>{{ mb_strtoupper($cargo) }}</span>
            </div>
        </div>
        <div class="doc-assinatura">
            <div class="doc-assinatura-linha">
                <strong>Setor (Responsável)</strong>
                <span>{{ mb_strtoupper($responsavel) }}</span>
                <span>{{ mb_strtoupper($cargo) }}</span>
            </div>
        </div>
    </div>

    <p class="doc-nota" style="margin-top: 30px;">
        Documento assinado eletronicamente por <b>{{ mb_strtoupper($responsavel) }} - {{ $cpf ?: 'NÃO INFORMADO' }}</b>, <b>{{ mb_strtoupper($cargo) }}</b>, em <b>{{ $dataAssinatura }}</b>, conforme art. 1º do Ato Normativo TJES Nº 75/2018. Código de Validação <b>{{ $numeroTermo }}</b>.
    </p>
</x-impressao::documento>
