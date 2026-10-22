<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao" :periodo="false">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="conta_titulo"
        :somar="['valor_reavaliado', 'depreciacao_acumulada', 'valor']"
        :total="true"
        :totalLabelSpan="6"
        :grupoRotulo="fn ($v, $l) => 'Conta Contábil: ' . ($l['conta_codigo'] ?? '') . ' - ' . $v"
        :colunas="[
            'num_registro' => ['label' => 'Núm. Registro', 'align' => 'center'],
            'inscricao_generica' => ['label' => 'Insc. Genérica', 'align' => 'center'],
            'inscricao_imobiliaria' => ['label' => 'Insc. Imobiliária', 'align' => 'center'],
            'descricao' => ['label' => 'Descrição do bem', 'width' => '22%'],
            'data_aquisicao' => ['label' => 'Data Aquisição', 'align' => 'center'],
            'localizacao' => ['label' => 'Localização Atual', 'width' => '20%'],
            'valor_reavaliado' => ['label' => 'Valor Hist. ou Reavaliado', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_acumulada' => ['label' => 'Depreciação Acumulada', 'align' => 'right', 'fmt' => 'moeda'],
            'valor' => ['label' => 'Valor Contábil', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
