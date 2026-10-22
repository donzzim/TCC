<x-relatorios::pagina :titulo="$titulo" secao="Setor de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['entrada_compra', 'entrada_doacao', 'entrada_construcao', 'entrada_desapropriacao', 'entrada_outras', 'total_entrada', 'saida_alienacao', 'saida_doacao', 'saida_perdas', 'saida_outras', 'total_saida']"
        :total="true"
        :totalLabelSpan="2"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'descricao' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '18%'],
            'entrada_compra' => ['label' => 'Compras', 'align' => 'right', 'fmt' => 'moeda'],
            'entrada_doacao' => ['label' => 'Doação', 'align' => 'right', 'fmt' => 'moeda'],
            'entrada_construcao' => ['label' => 'Construção/Reforma', 'align' => 'right', 'fmt' => 'moeda'],
            'entrada_desapropriacao' => ['label' => 'Desapropriação', 'align' => 'right', 'fmt' => 'moeda'],
            'entrada_outras' => ['label' => 'Ent. Outras', 'align' => 'right', 'fmt' => 'moeda'],
            'total_entrada' => ['label' => 'Total Entradas', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_alienacao' => ['label' => 'Alienação', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_doacao' => ['label' => 'Saí. Doação', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_perdas' => ['label' => 'Perdas', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_outras' => ['label' => 'Saí. Outras', 'align' => 'right', 'fmt' => 'moeda'],
            'total_saida' => ['label' => 'Total Saídas', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
