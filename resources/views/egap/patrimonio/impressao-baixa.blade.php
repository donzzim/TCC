<x-impressao::documento
    titulo="Relatório de Baixa dos Bens Patrimoniais"
    titulo-aba="Relatório de Baixa - Bens Patrimoniais"
    secao="Seção de Patrimônio"
    kicker="Baixa de bens patrimoniais"
    :identificador="'Processo ' . $numeroProcesso"
    :data-emissao="now()->format('d/m/Y H:i')"
    rodape="e-GAPes · Seção de Patrimônio"
>
    <dl class="doc-campos">
        <div class="doc-campo">
            <dt>Processo Nº</dt>
            <dd>{{ $numeroProcesso }}</dd>
        </div>
        <div class="doc-campo">
            <dt>CNPJ</dt>
            <dd>{{ $baixa->RequisitanteCnpj ?? 'NÃO INFORMADO' }}</dd>
        </div>
        <div class="doc-campo largo">
            <dt>Requisitante</dt>
            <dd>{{ mb_strtoupper($baixa->Requisitante ?? 'NÃO INFORMADO') }}</dd>
        </div>
        @if (! empty($baixa->Endereco))
            <div class="doc-campo largo">
                <dt>Endereço</dt>
                <dd>{{ mb_strtoupper($baixa->Endereco) }}</dd>
            </div>
        @endif
    </dl>

    <div class="doc-table-wrap">
        <table class="rel confortavel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 40px;">Item</th>
                <th class="a-center" scope="col" style="width: 90px;">Pat. c/ cód. barras</th>
                <th class="a-left" scope="col">Descrição do bem / Marca / Modelo</th>
                <th class="a-right" scope="col" style="width: 92px;">Valor aquisição</th>
                <th class="a-right" scope="col" style="width: 92px;">Valor reavaliado</th>
                <th class="a-center" scope="col" style="width: 80px;">Nº série</th>
                <th class="a-center" scope="col" style="width: 96px;">Processo baixa</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($itens as $index => $item)
                <tr>
                    <td class="a-center">{{ $index + 1 }}</td>
                    <td class="a-center forte">{{ $item->NumPatrimonio }}</td>
                    <td>{{ $item->MaterialDescricao }}</td>
                    <td class="a-right nowrap">R$ {{ number_format((float) ($item->ValorAquisicao ?? 0), 2, ',', '.') }}</td>
                    <td class="a-right nowrap">R$ {{ number_format((float) ($item->ValordaReavaliacao ?? 0), 2, ',', '.') }}</td>
                    <td class="a-center">{{ $item->NumerodeSerie ?? '-' }}</td>
                    <td class="a-center">{{ $numeroProcesso }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="7">Nenhum bem vinculado a este processo de baixa.</td>
                </tr>
            @endforelse

            <tr class="total">
                <td class="a-right" colspan="3">TOTAL</td>
                <td class="a-right nowrap">R$ {{ number_format((float) $totalAquisicao, 2, ',', '.') }}</td>
                <td class="a-right nowrap">R$ {{ number_format((float) $totalReavaliado, 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="doc-assinaturas" style="--colunas: 1; max-width: 60%; margin-left: auto; margin-right: auto;">
        <div class="doc-assinatura">
            <div class="doc-assinatura-linha">
                <strong>Comissão de Inventário e Controle Patrimonial</strong>
                <span>Seção de Patrimônio - TJES</span>
            </div>
        </div>
    </div>
</x-impressao::documento>
