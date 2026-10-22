<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao" :periodo="false">

    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="setor"
        :somar="['valor_aquisicao']"
        :total="true"
        :grupoRotulo="fn ($v) => 'Setor: ' . ($v ?: '—')"
        :colunas="[
            'num_patrimonio' => ['label' => 'Patrimônio', 'align' => 'center'],
            'descricao' => ['label' => 'Descrição / Marca / Modelo', 'width' => '25%', 'campo' => 'descricao'],
            'marca' => ['label' => 'Marca'],
            'modelo' => ['label' => 'Modelo'],
            'data_incorporacao' => ['label' => 'Data Incorp.', 'align' => 'center'],
            'fornecedor' => ['label' => 'Fornecedor'],
            'numero_processo' => ['label' => 'Processo'],
            'nota_fiscal' => ['label' => 'Nota Fiscal'],
            'grupo' => ['label' => 'Inventário'],
            'valor_aquisicao' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />

    @if (! empty($quadro_bens_ti))
        <div class="quebra"></div>
        <h2 class="bloco">Bens de TI — por Descrição</h2>
        <x-relatorios::tabela
            :linhas="$quadro_bens_ti"
            :colunas="[
                'descricao' => ['label' => 'Descrição', 'width' => '25%'],
                'inventariado_a_partir_2015' => ['label' => 'Inv. a partir 2015', 'align' => 'right', 'fmt' => 'inteiro'],
                'valor_a' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
                'inventariado_antes_2015' => ['label' => 'Inv. antes 2015', 'align' => 'right', 'fmt' => 'inteiro'],
                'valor_b' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
                'inventario_online' => ['label' => 'Inv. Online', 'align' => 'right', 'fmt' => 'inteiro'],
                'valor_c' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
                'a_inventariar' => ['label' => 'A inventariar', 'align' => 'right', 'fmt' => 'inteiro'],
                'valor_d' => ['label' => 'Valor Aquisição', 'align' => 'right', 'fmt' => 'moeda'],
                'total' => ['label' => 'Total', 'align' => 'right', 'fmt' => 'inteiro'],
            ]" />
    @endif

    @if (! empty($grafico_bens_ti))
        <h2 class="bloco">Acurácia dos Bens de TI</h2>
        <x-relatorios::tabela
            :linhas="$grafico_bens_ti"
            :somar="['total']"
            :total="true"
            :colunas="[
                'grupo' => ['label' => 'Situação do Inventário', 'width' => '50%'],
                'total' => ['label' => 'Quantidade', 'align' => 'right', 'fmt' => 'inteiro'],
            ]" />
    @endif
</x-relatorios::pagina>
