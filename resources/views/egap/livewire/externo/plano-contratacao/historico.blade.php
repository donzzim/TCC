@php
    use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
    /** @var \Illuminate\Support\Collection<int, \App\Models\egap\PlanoContratacao\HistoricoPlano> $historico */
@endphp

@if ($historico->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Sem histórico registrado.</p>
@else
    <ol class="relative border-s border-gray-200 dark:border-white/10 ms-3">
        @foreach ($historico as $registro)
            <li class="mb-6 ms-6">
                <span
                    class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40">
                    <x-filament::icon icon="heroicon-m-arrow-path"
                                      class="h-3 w-3 text-primary-600 dark:text-primary-400"/>
                </span>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ SituacaoPlano::rotulo((int) $registro->id_etapa) }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ optional($registro->data_modificacao)->format('d/m/Y H:i') ?? '—' }}
                    &middot; {{ $registro->usuario?->name ?? 'Sistema' }}
                </p>
            </li>
        @endforeach
    </ol>
@endif
