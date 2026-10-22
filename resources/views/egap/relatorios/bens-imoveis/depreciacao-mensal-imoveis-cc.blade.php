@php
    $medidas = ['valor', 'valor_residual', 'depreciacao_mensal', 'depreciacao_acumulada', 'valor_liquido_contabil', 'depreciacao_acumulada_reavaliacao'];
    $colsMedidas = [
        'valor' => ['label' => 'Valor', 'align' => 'right', 'fmt' => 'moeda'],
        'valor_residual' => ['label' => 'Valor Residual', 'align' => 'right', 'fmt' => 'moeda'],
        'depreciacao_mensal' => ['label' => 'Depreciação Mensal', 'align' => 'right', 'fmt' => 'moeda'],
        'depreciacao_acumulada' => ['label' => 'Depreciação Acumulada', 'align' => 'right', 'fmt' => 'moeda'],
        'valor_liquido_contabil' => ['label' => 'Valor Líquido Contábil', 'align' => 'right', 'fmt' => 'moeda'],
        'depreciacao_acumulada_reavaliacao' => ['label' => 'Dep. Acum. das Saídas', 'align' => 'right', 'fmt' => 'moeda'],
    ];
@endphp

<x-relatorios::pagina :titulo="$titulo" secao="Setor de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">

    <h2 class="bloco">Detalhamento por Centro de Custo</h2>
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="centro_custo_codigo"
        :somar="$medidas"
        :totalLabelSpan="4"
        :grupoRotulo="fn ($v, $l) => 'Centro de Custo: ' . $v . ' — ' . ($l['centro_custo_descricao'] ?? '')"
        :colunas="array_merge([
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'item_patrimonial' => ['label' => 'Item Patrimonial'],
            'descricao_conta' => ['label' => 'Descrição', 'width' => '18%'],
            'descricao_imovel' => ['label' => 'Imóvel', 'width' => '20%'],
        ], $colsMedidas)" />

    <div class="quebra"></div>
    <h2 class="bloco">Resumo por Centro de Custo</h2>
    <x-relatorios::tabela
        :linhas="$resumos['por_centro_custo']"
        :somar="$medidas"
        :total="true"
        :totalLabelSpan="2"
        :colunas="array_merge([
            'codigo' => ['label' => 'Código', 'align' => 'center'],
            'centro_custo' => ['label' => 'Centro de Custo', 'width' => '25%'],
        ], $colsMedidas)" />

    <div class="quebra"></div>
    <h2 class="bloco">Resumo por Conta Contábil</h2>
    <x-relatorios::tabela
        :linhas="$resumos['por_conta']"
        :somar="$medidas"
        :total="true"
        :totalLabelSpan="3"
        :colunas="array_merge([
            'conta_contabil' => ['label' => 'Código', 'align' => 'center'],
            'produto' => ['label' => 'Produto'],
            'descricao' => ['label' => 'Descrição', 'width' => '20%'],
        ], $colsMedidas)" />

    <div class="quebra"></div>
    <h2 class="bloco">Resumo por Item Patrimonial</h2>
    <x-relatorios::tabela
        :linhas="$resumos['por_item_patrimonial']"
        :somar="$medidas"
        :total="true"
        :totalLabelSpan="1"
        :colunas="array_merge([
            'item_patrimonial' => ['label' => 'Item Patrimonial'],
        ], $colsMedidas)" />
</x-relatorios::pagina>
