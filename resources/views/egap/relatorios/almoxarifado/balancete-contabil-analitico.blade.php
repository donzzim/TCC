<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="tipo"
        :somar="['qtd_anterior', 'valor_anterior', 'qtd_entrada', 'valor_entrada', 'qtd_saida', 'valor_saida', 'qtd_atual', 'saldo_atual']"
        :total="true"
        :colunas="[
            'descricao_detalhada' => ['label' => 'Descrição', 'width' => '30%'],
            'qtd_anterior' => ['label' => 'Qtd Ant.', 'align' => 'right', 'fmt' => 'inteiro'],
            'valor_anterior' => ['label' => 'Valor Ant.', 'align' => 'right', 'fmt' => 'moeda'],
            'qtd_entrada' => ['label' => 'Qtd Entr.', 'align' => 'right', 'fmt' => 'inteiro'],
            'valor_entrada' => ['label' => 'Valor Entr.', 'align' => 'right', 'fmt' => 'moeda'],
            'qtd_saida' => ['label' => 'Qtd Saída', 'align' => 'right', 'fmt' => 'inteiro'],
            'valor_saida' => ['label' => 'Valor Saída', 'align' => 'right', 'fmt' => 'moeda'],
            'qtd_atual' => ['label' => 'Qtd Atual', 'align' => 'right', 'fmt' => 'inteiro'],
            'saldo_atual' => ['label' => 'Saldo Atual', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
