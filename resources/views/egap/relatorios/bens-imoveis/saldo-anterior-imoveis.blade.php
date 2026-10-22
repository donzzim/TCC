<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['saldo_anterior', 'valorobra_entrada', 'ajuste_contabil_entrada', 'ajuste_contabil_saida']"
        :total="true"
        :totalLabelSpan="1"
        :colunas="[
            'descricao' => ['label' => 'Imóvel', 'width' => '20%'],
            'saldo_anterior' => ['label' => 'Saldo Anterior', 'align' => 'right', 'fmt' => 'moeda'],
            'valorobra_entrada' => ['label' => 'Entrada Obras', 'align' => 'right', 'fmt' => 'moeda'],
            'ajuste_contabil_entrada' => ['label' => 'Entrada Ajustes', 'align' => 'right', 'fmt' => 'moeda'],
            'ajuste_contabil_saida' => ['label' => 'Saída Ajustes', 'align' => 'right', 'fmt' => 'moeda'],
            'data_aquisicao' => ['label' => 'Data Aquisição', 'align' => 'center'],
            'valor_historico' => ['label' => 'Valor Hist. 1ª Aval.', 'align' => 'right', 'fmt' => 'moeda'],
            'data_baixa' => ['label' => 'Data Baixa', 'align' => 'center'],
            'data_situacao' => ['label' => 'Data Trans. Ativo', 'align' => 'center'],
            'data_reavaliacao' => ['label' => 'Data Reavaliação', 'align' => 'center'],
            'valor_reavaliacao' => ['label' => 'Valor Últ. Reavaliação', 'align' => 'right', 'fmt' => 'moeda'],
            'situacao' => ['label' => 'Situação'],
        ]" />
</x-relatorios::pagina>
