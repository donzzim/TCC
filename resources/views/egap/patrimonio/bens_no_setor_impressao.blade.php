@php
    use App\Filament\Egap\Externo\Groups\Patrimonio\Components\BensNoSetorTable;

    $total = $bens->count();
@endphp

<x-impressao::documento
    titulo="Bens no Setor"
    :titulo-aba="'Bens no Setor - ' . $setor"
    secao="Seção de Patrimônio"
    kicker="Inventário de bens patrimoniais"
    orientacao="paisagem"
    :identificador="$total . ' ' . ($total === 1 ? 'bem' : 'bens')"
    :data-emissao="now()->format('d/m/Y H:i')"
    rodape="e-GAPes · Bens no Setor"
    :rodape-direita="$setor"
>
    <dl class="doc-campos">
        <div class="doc-campo">
            <dt>Unidade</dt>
            <dd>{{ mb_strtoupper($unidade ?: 'NÃO INFORMADA') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Setor</dt>
            <dd>{{ mb_strtoupper($setor) }}</dd>
        </div>
    </dl>

    <div class="doc-table-wrap">
        <table class="rel confortavel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 40px;">Item</th>
                <th class="a-center" scope="col" style="width: 76px;">Patrimônio</th>
                <th class="a-center" scope="col" style="width: 76px;">Pat. anterior</th>
                <th class="a-left" scope="col">Material / Marca / Modelo</th>
                <th class="a-left" scope="col" style="width: 170px;">Complemento do setor</th>
                <th class="a-center" scope="col" style="width: 90px;">Conservação</th>
                <th class="a-center" scope="col" style="width: 130px;">Situação</th>
                <th class="a-center" scope="col" style="width: 70px;">Termo</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($bens as $bem)
                <tr>
                    <td class="a-center">{{ $loop->iteration }}</td>
                    <td class="a-center forte">{{ $bem->NumPatrimonio }}</td>
                    <td class="a-center">{{ $bem->NumerodePatAnterior ?: '-' }}</td>
                    <td>
                        {{ $bem->Descricao }}
                        @if ($marcaModelo = collect([$bem->marcaRef?->Descricao, $bem->modeloRef?->descricao])->filter()->implode(' / '))
                            <br><span style="color: var(--muted);">{{ $marcaModelo }}</span>
                        @endif
                    </td>
                    <td>{{ $bem->complementoSetorRef?->descricao ?: '-' }}</td>
                    <td class="a-center">{{ $bem->EstadodeConservacao ?: '-' }}</td>
                    <td class="a-center">{{ $bem->situacaoBemRef?->descricao ?: '-' }}</td>
                    <td class="a-center nowrap">{{ BensNoSetorTable::termoPendente($bem) ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="8">Nenhum bem encontrado no setor.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-impressao::documento>
