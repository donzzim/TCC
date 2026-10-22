<x-relatorios::pagina :titulo="$titulo" secao="Setor de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao" orientacao="retrato">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['total_bem', 'total_aquisicao', 'total_reavaliacao']"
        :total="true"
        :colunas="[
            'processo_baixa' => ['label' => 'Processo Baixa'],
            'total_bem' => ['label' => 'Quantidade', 'align' => 'right', 'fmt' => 'inteiro'],
            'total_aquisicao' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
            'total_reavaliacao' => ['label' => 'Valor Reavaliado', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
