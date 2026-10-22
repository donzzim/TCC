<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">

    @forelse ($dados as $processo)
        @if (! $loop->first)<div class="quebra"></div>@endif

        <table class="rel" style="margin-bottom: 6px;">
            <tbody>
                <tr><td style="width: 15%;"><strong>Processo Nº</strong></td><td>{{ $processo['numero_processo'] }}</td>
                    <td style="width: 12%;"><strong>Data da Baixa</strong></td><td>{{ $processo['data_baixa'] }}</td></tr>
                <tr><td><strong>Requisitante</strong></td><td>{{ $processo['requisitante'] }}</td>
                    <td><strong>CNPJ</strong></td><td>{{ $processo['requisitante_cnpj'] }}</td></tr>
                @if ($processo['observacao'])
                    <tr><td><strong>Observação</strong></td><td colspan="3">{{ $processo['observacao'] }}</td></tr>
                @endif
                @if ($processo['endereco'])
                    <tr><td><strong>Endereço</strong></td><td colspan="3">{{ $processo['endereco'] }}</td></tr>
                @endif
            </tbody>
        </table>

        <x-relatorios::tabela
            :linhas="$processo['itens']"
            agruparPor="conta_codigo"
            :somar="['valor_bruto', 'valor_liquido_contabil', 'depreciacao_acumulada']"
            :total="true"
            :totalLabelSpan="4"
            :grupoRotulo="fn ($v) => 'Conta Contábil: ' . ($v ?: '—')"
            :colunas="[
                'num_patrimonio' => ['label' => 'Patrim.', 'align' => 'center'],
                'descricao' => ['label' => 'Descrição / Marca / Modelo', 'width' => '30%'],
                'marca' => ['label' => 'Marca'],
                'situacao' => ['label' => 'Tipo da Baixa', 'align' => 'center'],
                'valor_bruto' => ['label' => 'Valor Bruto', 'align' => 'right', 'fmt' => 'moeda'],
                'valor_liquido_contabil' => ['label' => 'Valor Líq. Cont.', 'align' => 'right', 'fmt' => 'moeda'],
                'depreciacao_acumulada' => ['label' => 'Dep. Acumul.', 'align' => 'right', 'fmt' => 'moeda'],
            ]" />
    @empty
        <p class="vazio">Nenhum bem baixado no período informado.</p>
    @endforelse

    @if (! empty($resumo_depreciacao_saidas))
        <div class="quebra"></div>
        <h2 class="bloco" style="text-align: center;">Depreciação Acumulada das Saídas</h2>
        <x-relatorios::tabela
            :linhas="$resumo_depreciacao_saidas"
            :somar="['valor_bruto', 'valor_liquido_contabil', 'depreciacao_acumulada']"
            :total="true"
            :totalLabelSpan="2"
            :colunas="[
                'conta_codigo' => ['label' => 'Conta Contábil', 'align' => 'center'],
                'numero_processo' => ['label' => 'Processo'],
                'valor_bruto' => ['label' => 'Valor Bruto', 'align' => 'right', 'fmt' => 'moeda'],
                'valor_liquido_contabil' => ['label' => 'Valor Líquido', 'align' => 'right', 'fmt' => 'moeda'],
                'depreciacao_acumulada' => ['label' => 'Depreciação Acumulada', 'align' => 'right', 'fmt' => 'moeda'],
            ]" />
    @endif
</x-relatorios::pagina>
