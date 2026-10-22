<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="descricao_detalhada"
        :somar="['quantidade_atual', 'quantidade_consumida']"
        :totalLabelSpan="1"
        :grupoRotulo="fn ($v) => 'Material: ' . ($v ?: '—')"
        :colunas="[
            'unidade_organizacional' => ['label' => 'Unidade', 'width' => '40%'],
            'quantidade_atual' => ['label' => 'Qtde Atual', 'align' => 'right', 'fmt' => 'decimal'],
            'quantidade_consumida' => ['label' => 'Qtde Consumida', 'align' => 'right', 'fmt' => 'decimal'],
            'consumo_medio' => ['label' => 'Consumo Médio', 'align' => 'right', 'fmt' => 'decimal'],
            'meses' => ['label' => 'Qtde Mês', 'align' => 'center', 'fmt' => 'inteiro'],
            'dias' => ['label' => 'Dias', 'align' => 'center', 'fmt' => 'inteiro'],
        ]" />
</x-relatorios::pagina>
