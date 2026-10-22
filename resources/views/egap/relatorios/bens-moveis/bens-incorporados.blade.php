<x-relatorios::pagina :titulo="$titulo" secao="Setor de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="conta_codigo"
        :somar="['valor']"
        :total="true"
        :grupoRotulo="fn ($v) => 'Conta Contábil: ' . ($v ?: '—')"
        :colunas="[
            'num_patrimonio' => ['label' => 'Patrim.', 'align' => 'center'],
            'descricao' => ['label' => 'Descrição', 'width' => '25%'],
            'marca' => ['label' => 'Marca'],
            'modelo' => ['label' => 'Modelo'],
            'forma_aquisicao' => ['label' => 'Forma Aquisição', 'align' => 'center'],
            'setor' => ['label' => 'Setor'],
            'situacao' => ['label' => 'Situação', 'align' => 'center'],
            'data_incorporacao' => ['label' => 'Data Incorp.', 'align' => 'center'],
            'valor' => ['label' => 'Valor', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
