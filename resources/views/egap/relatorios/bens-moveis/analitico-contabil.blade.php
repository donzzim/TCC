<x-relatorios::pagina :titulo="$titulo" secao="Setor de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['valor_aquisicao', 'valor_liquido_contabil', 'valor_residual', 'valor_reavaliado']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_codigo' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'num_patrimonio' => ['label' => 'Patrimônio', 'align' => 'center'],
            'data_aquisicao' => ['label' => 'Data Aquisição', 'align' => 'center'],
            'valor_aquisicao' => ['label' => 'Valor de Entrada', 'align' => 'right', 'fmt' => 'moeda'],
            'vida_util_remanescente' => ['label' => 'Vida Útil Rem.', 'align' => 'center'],
            'data_disponibilizacao' => ['label' => 'Data Disponib.', 'align' => 'center'],
            'valor_liquido_contabil' => ['label' => 'Valor Líq. Contábil', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_residual' => ['label' => 'Valor Residual', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_reavaliado' => ['label' => 'Valor Reavaliado', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
