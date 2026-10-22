@php
    $flow = $autoConfigForm->getFluxoPreview($get);

    $toneClasses = [
        'amber' => [
            'badge' =>
                'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30',
            'dot' => 'bg-amber-500 ring-amber-500/20',
            'accent' => 'border-s-amber-500',
        ],
        'blue' => [
            'badge' =>
                'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/30',
            'dot' => 'bg-sky-500 ring-sky-500/20',
            'accent' => 'border-s-sky-500',
        ],
        'emerald' => [
            'badge' =>
                'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30',
            'dot' => 'bg-emerald-500 ring-emerald-500/20',
            'accent' => 'border-s-emerald-500',
        ],
        'gray' => [
            'badge' =>
                'bg-gray-50 text-gray-600 ring-gray-500/15 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
            'dot' => 'bg-gray-400 ring-gray-400/20',
            'accent' => 'border-s-gray-300 dark:border-s-gray-600',
        ],
        'rose' => [
            'badge' =>
                'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/30',
            'dot' => 'bg-rose-500 ring-rose-500/20',
            'accent' => 'border-s-rose-500',
        ],
        'sky' => [
            'badge' =>
                'bg-cyan-50 text-cyan-700 ring-cyan-600/20 dark:bg-cyan-400/10 dark:text-cyan-300 dark:ring-cyan-400/30',
            'dot' => 'bg-cyan-500 ring-cyan-500/20',
            'accent' => 'border-s-cyan-500',
        ],
        'slate' => [
            'badge' =>
                'bg-slate-100 text-slate-700 ring-slate-600/10 dark:bg-white/10 dark:text-slate-200 dark:ring-white/10',
            'dot' => 'bg-slate-500 ring-slate-500/20',
            'accent' => 'border-s-slate-400',
        ],
    ];

    $severityClasses = [
        'error' =>
            'border-rose-300/70 bg-rose-50/70 text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/20 dark:text-rose-200',
        'info' =>
            'border-sky-300/70 bg-sky-50/70 text-sky-800 dark:border-sky-900/70 dark:bg-sky-950/20 dark:text-sky-200',
        'warning' =>
            'border-amber-300/70 bg-amber-50/70 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/20 dark:text-amber-200',
    ];

    $statusTone = $toneClasses[$flow['status']['tone']] ?? $toneClasses['gray'];
@endphp

<div class="space-y-6">
    <section class="space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0 space-y-1">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Fluxo da automacao
                </h3>

                <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-400">
                    Timeline montada a partir das datas do curso, regras do formulario, breakpoints manuais e estado
                    operacional em
                    <code
                        class="rounded-md bg-gray-100 px-1.5 py-0.5 text-xs text-gray-800 dark:bg-white/10 dark:text-gray-200">auto_steps</code>.
                </p>
            </div>

            <div class="flex shrink-0 flex-col items-start gap-1 md:items-end">
                <span
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusTone['badge'] }}">
                    {{ $flow['status']['label'] }}
                </span>

                <p class="max-w-md text-left text-xs leading-5 text-gray-500 dark:text-gray-400 md:text-right">
                    {{ $flow['status']['description'] }}
                </p>
            </div>
        </div>

        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Etapas ativas
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $flow['summary']['enabled_steps'] }}</dd>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Breakpoints
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $flow['summary']['configured_breakpoints'] }}</dd>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pendencias</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $flow['summary']['pending_warnings'] }}</dd>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Ultima analise
                </dt>
                <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $flow['summary']['last_analyzed_at'] ?? 'Ainda nao analisado' }}</dd>
            </div>
        </dl>
    </section>

    @if (count($flow['warnings']) > 0)
        <section
            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-100">
            <h4 class="text-sm font-medium">Pontos para revisar antes de ligar o fluxo</h4>

            <div class="mt-3 space-y-2 text-sm">
                @foreach ($flow['warnings'] as $warning)
                    <p class="rounded-md bg-white/70 px-3 py-2 dark:bg-white/5">
                        {{ $warning['message'] }}
                    </p>
                @endforeach
            </div>
        </section>
    @endif

    <section class="space-y-4">
        <div>
            <h4 class="text-sm font-medium text-gray-950 dark:text-white">Timeline</h4>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Eventos sem data aparecem no final para evidenciar lacunas da configuracao.
            </p>
        </div>

        <ol
            class="relative space-y-4 before:absolute before:bottom-3 before:left-3 before:top-3 before:w-px before:bg-gray-200 dark:before:bg-white/10">
            @foreach ($flow['events'] as $event)
                @php
                    $tone = $toneClasses[$event['tone']] ?? $toneClasses['gray'];
                    $isDisabled = $event['status_key'] === 'disabled';
                @endphp

                <li class="relative flex gap-4">
                    <div
                        class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white dark:bg-gray-900">
                        <span class="h-3 w-3 rounded-full ring-4 {{ $tone['dot'] }}"></span>
                    </div>

                    <article
                        class="min-w-0 flex-1 rounded-lg border border-s-4 border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5 {{ $tone['accent'] }} {{ $isDisabled ? 'opacity-70' : '' }}">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $event['group'] }}
                                    </span>

                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $tone['badge'] }}">
                                        {{ $event['status_label'] }}
                                    </span>
                                </div>

                                <h5 class="mt-1 break-words text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $event['title'] }}
                                </h5>

                                <p class="mt-1 break-words text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    {{ $event['description'] }}
                                </p>
                            </div>

                            <div
                                class="shrink-0 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 dark:border-white/10 dark:bg-white/5 lg:text-right">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $event['has_date'] ? 'Quando' : 'Referencia' }}
                                </p>
                                <p class="mt-0.5 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $event['date_label'] }}
                                </p>
                            </div>
                        </div>

                        @if (count($event['details']) > 0)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($event['details'] as $detail)
                                    <span
                                        class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                                        {{ $detail }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if (count($event['breakpoints']) > 0)
                            <div class="mt-4 space-y-2">
                                @foreach ($event['breakpoints'] as $breakpoint)
                                    @php
                                        $severityClass =
                                            $severityClasses[$breakpoint['severity']] ?? $severityClasses['warning'];
                                    @endphp

                                    <div class="rounded-lg border border-dashed p-3 text-sm {{ $severityClass }}">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="break-words font-medium">
                                                    {{ $breakpoint['label'] }}
                                                </p>

                                                <p class="mt-1 text-xs opacity-80">
                                                    {{ $breakpoint['position_label'] }}
                                                </p>
                                            </div>

                                            <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs">
                                                <span class="rounded-md bg-white/70 px-2 py-0.5 dark:bg-white/10">
                                                    {{ $breakpoint['blocking'] ? 'Pausa o fluxo' : 'Somente marca' }}
                                                </span>

                                                <span class="rounded-md bg-white/70 px-2 py-0.5 dark:bg-white/10">
                                                    {{ $breakpoint['notify'] ? 'Notifica' : 'Nao notifica' }}
                                                </span>

                                                @if (($breakpoint['pending'] ?? false) && ($breakpoint['pending_blocking'] ?? false))
                                                    <button
                                                        type="button"
                                                        wire:click="continueBreakpoint('{{ $breakpoint['code'] }}')"
                                                        wire:loading.attr="disabled"
                                                        title="Liberar e continuar a automacao"
                                                        class="inline-flex items-center gap-1.5 rounded-md bg-white/80 px-2.5 py-1.5 font-semibold shadow-sm ring-1 ring-inset ring-current/20 transition hover:bg-white disabled:cursor-wait disabled:opacity-60 dark:bg-white/10 dark:hover:bg-white/15"
                                                    >
                                                        <x-heroicon-m-play class="h-3.5 w-3.5" />
                                                        Continuar automacao
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        @if (filled($breakpoint['description'] ?? null))
                                            <p class="mt-2 break-words text-sm opacity-90">
                                                {{ $breakpoint['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                </li>
            @endforeach
        </ol>
    </section>
</div>
