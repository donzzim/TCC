<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao" orientacao="retrato">

    @forelse ($dados as $pedido)
        <h2 class="bloco">
            Pedido {{ $pedido['numero'] }} — {{ $pedido['setor'] ?? '—' }} — {{ $pedido['solicitante'] ?? '—' }}
            <span style="font-weight: normal;">({{ $pedido['data'] }})</span>
        </h2>
        <x-relatorios::tabela
            :linhas="$pedido['itens']"
            :somar="['quantidade_solicitada', 'quantidade_atendida', 'valor_total']"
            :total="true"
            :colunas="[
                'descricao_detalhada' => ['label' => 'Descrição', 'width' => '35%'],
                'elemento_codigo' => ['label' => 'Elemento', 'align' => 'center'],
                'unidade' => ['label' => 'Unid. Medida', 'align' => 'center'],
                'quantidade_solicitada' => ['label' => 'Qtde Solic.', 'align' => 'right', 'fmt' => 'inteiro'],
                'quantidade_atendida' => ['label' => 'Qtde Atend.', 'align' => 'right', 'fmt' => 'inteiro'],
                'valor_medio' => ['label' => 'Valor Médio', 'align' => 'right', 'fmt' => 'moeda'],
                'valor_total' => ['label' => 'Valor Total Médio', 'align' => 'right', 'fmt' => 'moeda'],
            ]" />
    @empty
        <p class="vazio">Nenhum pedido validado no período informado.</p>
    @endforelse
</x-relatorios::pagina>
