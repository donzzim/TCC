@php
    use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Pages\Monitoramento;
    use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;
    use App\Monitoramento\Leitura\Periodo;

    $widgets = [
        'visao-geral' => [
            [Widgets\SemaforoWidget::class, 'full'],
            [Widgets\IndicadoresGeraisWidget::class, 'full'],
            [Widgets\LatenciaChart::class, 'half'],
            [Widgets\VolumePorContextoChart::class, 'half'],
            [Widgets\ErrosChart::class, 'half'],
            [Widgets\UsuariosOnlineChart::class, 'half'],
        ],
        'desempenho' => [
            [Widgets\RotasLentasTable::class, 'full'],
            [Widgets\LivewireCarosTable::class, 'full'],
            [Widgets\RequisicoesLentasTable::class, 'full'],
        ],
    ][$aba] ?? [];

    $ultimaColeta = $this->ultimaColeta();
@endphp

<x-filament-panels::page>
    <div class="flex flex-col gap-4">
        <x-filament::tabs>
            @foreach (Monitoramento::ABAS as $chave => $rotulo)
                <x-filament::tabs.item
                    :active="$aba === $chave"
                    wire:click="$set('aba', '{{ $chave }}')"
                >
                    {{ $rotulo }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        <div class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-200">
                Período
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="periodo">
                        @foreach (Periodo::opcoes() as $valor => $rotulo)
                            <option value="{{ $valor }}">{{ $rotulo }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-200">
                Área
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="contexto">
                        <option value="">Todas</option>
                        @foreach (Monitoramento::CONTEXTOS as $valor => $rotulo)
                            <option value="{{ $valor }}">{{ $rotulo }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <p class="ms-auto text-sm text-gray-500 dark:text-gray-400" wire:poll.30s>
                @if ($ultimaColeta === null)
                    Nenhuma coleta registrada ainda. Rode <code>php artisan monitoramento:coletar</code> ou o agendador.
                @else
                    Última coleta há {{ max(0, now()->getTimestamp() - $ultimaColeta) }} s
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($widgets as [$classe, $largura])
                <div @class(['lg:col-span-2' => $largura === 'full'])>
                    @livewire($classe, ['periodo' => $periodo, 'contexto' => $contexto], key($aba.'-'.$classe))
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
