<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao" :periodo="false">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="conta_titulo"
        :somar="['quantidade', 'valor_aquisicao', 'amortizacao_acumulada', 'valor_liquido_contabil']"
        :total="true"
        :totalLabelSpan="2"
        :grupoRotulo="fn ($v, $l) => 'Conta Contábil: ' . ($l['conta_codigo'] ?? '') . ' - ' . $v"
        :colunas="[
            'inscricao_generica' => ['label' => 'Inscrição Genérica', 'align' => 'center'],
            'nome' => ['label' => 'Descrição', 'width' => '30%'],
            'quantidade' => ['label' => 'QTDE', 'align' => 'center', 'fmt' => 'inteiro'],
            'data_aquisicao' => ['label' => 'Data Aquisição', 'align' => 'center'],
            'valor_aquisicao' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
            'amortizacao_acumulada' => ['label' => 'Amortização Acumulada', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_liquido_contabil' => ['label' => 'Valor Contábil', 'align' => 'right', 'fmt' => 'moeda'],
            'vida_util_remanescente' => ['label' => 'Vida Útil Rem.', 'align' => 'center'],
        ]" />
</x-relatorios::pagina>
