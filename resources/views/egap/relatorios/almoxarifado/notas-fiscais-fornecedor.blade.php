@php
    $dados = collect($dados);
    $porFornecedor = $dados->groupBy('fornecedor_id');

    $qtdeNotas = $dados->count();
    $qtdeItens = $dados->sum(fn ($nf) => collect($nf['itens'])->sum('quantidade'));
    $somaTotal = $dados->sum(fn ($nf) => collect($nf['itens'])->sum('valor_total'));

    $fmtMoeda = fn ($v) => 'R$ ' . number_format((float) $v, 4, ',', '.');
@endphp

<x-relatorios::pagina :titulo="$titulo" secao="Seção de Almoxarifado"
    :filtros="$filtros" :data-emissao="$data_emissao" orientacao="retrato">

    @forelse ($porFornecedor as $notas)
        <h2 class="bloco">{{ $notas->first()['fornecedor'] ?? 'Sem fornecedor' }} — {{ $notas->first()['cnpj'] }}</h2>

        @foreach ($notas as $nf)
            <p style="margin: 8px 0 2px; font-weight: bold;">
                {{ $nf['tipo_documento'] }}: {{ $nf['num_documento'] }} — {{ $nf['data_documento'] }}
            </p>
            <x-relatorios::tabela
                :linhas="$nf['itens']"
                :somar="['quantidade', 'preco_unitario', 'valor_total']"
                :total="true"
                :colunas="[
                    'descricao_resumida' => ['label' => 'Descrição', 'width' => '55%'],
                    'elemento_codigo' => ['label' => 'Elemento', 'align' => 'center'],
                    'quantidade' => ['label' => 'Qtde', 'align' => 'right', 'fmt' => 'inteiro'],
                    'preco_unitario' => ['label' => 'Valor Unitário', 'align' => 'right', 'fmt' => 'moeda'],
                    'valor_total' => ['label' => 'Valor Total', 'align' => 'right', 'fmt' => 'moeda'],
                ]" />
        @endforeach
    @empty
        <p class="vazio">Nenhuma nota fiscal no período informado.</p>
    @endforelse

    <div class="quebra"></div>
    <h2 class="bloco" style="text-align: center;">Quadro Resumo das Notas Fiscais</h2>
    <table class="rel" style="width: 60%; margin: 0 auto;">
        <thead>
            <tr>
                <th class="a-center">Qtde. Notas Fiscais</th>
                <th class="a-center">Qtde. Itens</th>
                <th class="a-center">Soma do Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="a-center">{{ number_format($qtdeNotas, 0, '', '.') }}</td>
                <td class="a-center">{{ number_format($qtdeItens, 0, '', '.') }}</td>
                <td class="a-center">{{ $fmtMoeda($somaTotal) }}</td>
            </tr>
        </tbody>
    </table>
</x-relatorios::pagina>
