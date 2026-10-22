<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['valor', 'valor_residual', 'depreciacao_mensal', 'depreciacao_acumulada', 'valor_liquido_contabil', 'depreciacao_acumulada_reavaliacao']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'descricao_conta' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '18%'],
            'descricao_imovel' => ['label' => 'Imóvel', 'width' => '22%'],
            'valor' => ['label' => 'Valor Atual', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_residual' => ['label' => 'Valor Residual', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_mensal' => ['label' => 'Depreciação Mensal', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_acumulada' => ['label' => 'Depreciação Acumulada', 'align' => 'right', 'fmt' => 'moeda'],
            'valor_liquido_contabil' => ['label' => 'Valor Líquido Contábil', 'align' => 'right', 'fmt' => 'moeda'],
            'depreciacao_acumulada_reavaliacao' => ['label' => 'Dep. Acum. da Reavaliação', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
