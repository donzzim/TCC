<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao" :periodo="false">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="conta_titulo"
        :somar="['valor_aquisicao', 'valor_ajustado', 'valor_atual']"
        :total="true"
        :totalLabelSpan="3"
        :grupoRotulo="fn ($v, $l) => 'Conta Contábil: ' . ($l['conta_codigo'] ?? '') . ' - ' . $v"
        :colunas="[
            'num_patrimonio' => ['label' => 'Patrimônio', 'align' => 'center'],
            'descricao' => ['label' => 'Descrição do bem', 'width' => '30%'],
            'data_aquisicao' => ['label' => 'Data Aquisição', 'align' => 'center'],
            'valor_aquisicao' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_ajustado' => ['label' => 'Valor Ajustado', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_atual' => ['label' => 'Valor Atual', 'align' => 'right', 'fmt' => 'moeda'],
            'setor' => ['label' => 'Localização Atual'],
        ]" />
</x-relatorios::pagina>
