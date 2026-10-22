@props([
    'titulo',
    'secao' => null,
    'filtros' => null,
    'dataEmissao' => null,
    'periodo' => true,
    'orientacao' => 'paisagem',
])

@php
    /** @var \App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio|null $filtros */
    $temPeriodo = $periodo
        && $filtros
        && $filtros->dataInicio !== null
        && $filtros->dataTermino !== null;
    $temSituacao = $filtros
        && filled($filtros->situacaoContabil)
        && $filtros->situacaoContabil !== 'Todos';
@endphp

<x-impressao::documento
    :titulo="$titulo"
    :secao="$secao"
    kicker="Relatório gerencial"
    :orientacao="$orientacao"
    :data-emissao="$dataEmissao"
    rodape="e-GAPes · Relatórios Gerenciais"
>
    @if ($temPeriodo || $temSituacao)
        <section class="doc-campos em-linha" aria-label="Filtros aplicados">
            @if ($temPeriodo)
                <div class="doc-campo">
                    <span class="doc-campo-label">Período</span>
                    <span class="doc-campo-valor">
                        {{ $filtros->dataInicio->format('d/m/Y') }} a {{ $filtros->dataTermino->format('d/m/Y') }}
                    </span>
                </div>
            @endif

            @if ($temSituacao)
                <div class="doc-campo">
                    <span class="doc-campo-label">Situação do inventário</span>
                    <span class="doc-campo-valor">{{ $filtros->situacaoContabil }}</span>
                </div>
            @endif
        </section>
    @endif

    {{ $slot }}
</x-impressao::documento>
