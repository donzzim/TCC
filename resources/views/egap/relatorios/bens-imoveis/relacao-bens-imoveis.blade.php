<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao" :periodo="false">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="conta"
        :somar="['valor_historico', 'valor_atualizado']"
        :total="true"
        :totalLabelSpan="10"
        :grupoRotulo="fn ($v) => 'Relação dos Bens Imóveis — ' . ($v ?: '—')"
        :colunas="[
            'num_registro' => ['label' => 'Nº Registro', 'align' => 'center'],
            'descricao' => ['label' => 'Denominação', 'width' => '18%'],
            'data_aquisicao' => ['label' => 'Data Aquisição', 'align' => 'center'],
            'estado_conservacao' => ['label' => 'Estado Conservação', 'align' => 'center'],
            'conta_codigo' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'data_ingresso_contabil' => ['label' => 'Ingresso Contábil', 'align' => 'center'],
            'inscricao_generica' => ['label' => 'Inscrição Genérica', 'align' => 'center'],
            'endereco' => ['label' => 'Endereço', 'width' => '20%'],
            'area' => ['label' => 'Área Total', 'align' => 'right'],
            'area_edificacao' => ['label' => 'Área Construída', 'align' => 'right'],
            'valor_historico' => ['label' => 'Valor Histórico', 'align' => 'right', 'fmt' => 'moeda'],
            'data_reavaliacao' => ['label' => 'Últ. Reavaliação', 'align' => 'center'],
            'valor_atualizado' => ['label' => 'Valor Atualizado', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
