<x-impressao::documento
    titulo="Relatório de Bens Patrimoniais"
    secao="Seção de Patrimônio"
    kicker="Bens selecionados"
    orientacao="paisagem"
    :identificador="$bens->count() . ' ' . ($bens->count() === 1 ? 'bem' : 'bens')"
    rodape="e-GAPes · Seção de Patrimônio"
>
    <div class="doc-table-wrap">
        <table class="rel">
            <thead>
            <tr>
                <th class="a-center" scope="col">Patrimônio</th>
                <th class="a-center" scope="col">Número de série</th>
                <th class="a-left" scope="col">Descrição detalhada</th>
                <th class="a-center" scope="col">Marca</th>
                <th class="a-center" scope="col">Modelo</th>
                <th class="a-left" scope="col">Unidade judiciária</th>
                <th class="a-left" scope="col">Setor</th>
                <th class="a-left" scope="col">Complemento do setor</th>
                <th class="a-right" scope="col">Valor aquisição</th>
                <th class="a-center" scope="col">Situação</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($bens as $bem)
                <tr>
                    <td class="a-center forte">{{ $bem->NumPatrimonio }}</td>
                    <td class="a-center">{{ $bem->NumerodeSerie ?? '-' }}</td>
                    <td>{{ $bem->Descricao }}</td>
                    <td class="a-center">{{ $bem->marcaRef->descricao ?? '-' }}</td>
                    <td class="a-center">{{ $bem->modeloRef->descricao ?? '-' }}</td>
                    <td>{{ $bem->unidadeJudiciariaRef->setor ?? '-' }}</td>
                    <td>{{ $bem->setorRef->setor ?? '-' }}</td>
                    <td>{{ $bem->ComplementoSetor ?? '-' }}</td>
                    <td class="a-right nowrap">R$ {{ number_format((float) $bem->ValorAquisicao, 2, ',', '.') }}</td>
                    <td class="a-center">{{ $bem->situacaoBemRef->descricao_completa ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="10">Nenhum bem selecionado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-impressao::documento>
