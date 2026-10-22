<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['valor', 'depreciacao_acumulada', 'valor_liquido_contabil', 'valor_reavaliacao', 'ajuste_contabil']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'descricao_conta' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '20%'],
            'descricao_imovel' => ['label' => 'Imóvel', 'width' => '25%'],
            'valor' => ['label' => 'Valor Bruto', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_acumulada' => ['label' => 'Depreciação Acumul.', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_liquido_contabil' => ['label' => 'Valor Líq.', 'align' => 'right', 'fmt' => 'moeda'],
            'data_reavaliacao' => ['label' => 'Data Reavaliação', 'align' => 'center'],
            'valor_reavaliacao' => ['label' => 'Valor Atual', 'align' => 'right', 'fmt' => 'moeda'],
            'ajuste_contabil' => ['label' => 'Ajuste Contábil', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
