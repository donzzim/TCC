<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['valor', 'valor_reavaliacao', 'acerto_contabil']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'produto' => ['label' => 'Cód. Nat. Despesa'],
            'descricao' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '35%'],
            'valor' => ['label' => 'Valor Histórico', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_reavaliacao' => ['label' => 'Valor Reavaliado', 'align' => 'right', 'fmt' => 'moeda'],
            'acerto_contabil' => ['label' => 'Perda / Depreciação', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
