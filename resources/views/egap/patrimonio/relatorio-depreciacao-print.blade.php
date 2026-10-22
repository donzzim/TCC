<x-impressao::documento
    titulo="Cálculo de Depreciação Mensal"
    :titulo-aba="'Depreciação - Patrimônio ' . $record->NumPatrimonio"
    secao="Seção de Patrimônio"
    kicker="Bens patrimoniais"
    :identificador="'Patrimônio ' . $record->NumPatrimonio"
    :data-emissao="now()->format('d/m/Y H:i')"
    rodape="e-GAPes · Seção de Patrimônio"
>
    <dl class="doc-campos" style="--colunas: 3;">
        <div class="doc-campo">
            <dt>Patrimônio</dt>
            <dd>{{ $record->NumPatrimonio }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Conta contábil</dt>
            <dd>{{ $record->contaContabilRef->titulo ?? '1.2.3.1.1.01.42' }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Elemento de despesa</dt>
            <dd>{{ $record->elementoDespesaRef->DescricaodaClasse ?? 'Mobiliário em Geral' }}</dd>
        </div>
        <div class="doc-campo largo">
            <dt>Descrição</dt>
            <dd>{{ $record->Descricao }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Data de aquisição</dt>
            <dd>{{ \Carbon\Carbon::parse($record->DataCadastro)->format('d/m/Y') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Valor de aquisição</dt>
            <dd>R$ {{ number_format($record->ValorAquisicao, 2, ',', '.') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Vida útil · Valor residual</dt>
            <dd>{{ $vidaUtil }} meses · R$ {{ number_format($valorResidual, 2, ',', '.') }}</dd>
        </div>
    </dl>

    <div class="doc-table-wrap">
        <table class="rel">
            <thead>
            <tr>
                <th class="a-center" scope="col">Item</th>
                <th class="a-center" scope="col">Data cálculo</th>
                <th class="a-right" scope="col">Valor (R$)</th>
                <th class="a-center" scope="col">Vida útil (meses)</th>
                <th class="a-right" scope="col">Valor residual</th>
                <th class="a-right" scope="col">Depreciação mensal</th>
                <th class="a-right" scope="col">Depreciação acumulada</th>
                <th class="a-right" scope="col">Valor líquido contábil</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($dados as $index => $linha)
                <tr>
                    <td class="a-center">{{ count($dados) - $index }}</td>
                    <td class="a-center">{{ $linha['data'] }}</td>
                    <td class="a-right">{{ number_format($record->ValorAquisicao, 2, ',', '.') }}</td>
                    <td class="a-center">{{ $vidaUtil }}</td>
                    <td class="a-right">{{ number_format($valorResidual, 2, ',', '.') }}</td>
                    <td class="a-right">{{ number_format($linha['mensal'], 4, ',', '.') }}</td>
                    <td class="a-right">{{ number_format($linha['acumulada'], 4, ',', '.') }}</td>
                    <td class="a-right forte">{{ number_format($linha['liquido'], 4, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="8">Nenhuma depreciação calculada para este bem.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-impressao::documento>
