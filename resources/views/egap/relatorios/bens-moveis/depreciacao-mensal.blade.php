<x-relatorios::pagina :titulo="$titulo" secao="Setor de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['valor', 'valor_residual', 'depreciacao_mensal', 'depreciacao_acumulada', 'valor_liquido_contabil', 'depreciacao_acumulada_saida']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'produto' => ['label' => 'Cód. Nat. Despesa'],
            'descricao' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '25%'],
            'valor' => ['label' => 'Valor', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_residual' => ['label' => 'Valor Residual', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_mensal' => ['label' => 'Depreciação Mensal', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_acumulada' => ['label' => 'Depreciação Acumulada', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_liquido_contabil' => ['label' => 'Valor Líquido Contábil', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_acumulada_saida' => ['label' => 'Dep. Acum. das Saídas', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
