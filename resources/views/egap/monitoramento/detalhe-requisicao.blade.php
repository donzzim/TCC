@php
    $fmt = fn (?float $ms) => $ms === null ? '—' : ($ms >= 1000 ? number_format($ms / 1000, 2, ',', '.').' s' : number_format($ms, 0, ',', '.').' ms');
@endphp

@if ($registro === null)
    <p class="text-sm text-gray-600 dark:text-gray-300">Esta requisição já saiu da retenção (48 h).</p>
@else
    @php
        $phpMs = max(0, $registro->duracaoMs - $registro->sqlMs);
        $percentualSql = $registro->duracaoMs > 0 ? min(100, $registro->sqlMs / $registro->duracaoMs * 100) : 0;
        $consultas = $registro->detalhe['top_consultas'] ?? [];
        $erro = $registro->detalhe['erro'] ?? null;
    @endphp

    <div class="flex flex-col gap-5 text-sm">
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Rota</dt>
                <dd class="font-mono break-all text-gray-950 dark:text-white">{{ $registro->rota }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="text-gray-950 dark:text-white">{{ $registro->metodo }} · {{ $registro->status }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Quando</dt>
                <dd class="text-gray-950 dark:text-white">{{ now()->setTimestamp((int) $registro->instante)->format('d/m/Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Request ID</dt>
                <dd class="font-mono text-xs break-all text-gray-950 dark:text-white">{{ $registro->requestId }}</dd>
            </div>
        </dl>

        <div>
            <p class="mb-1 font-medium text-gray-950 dark:text-white">
                Duração total {{ $fmt($registro->duracaoMs) }}
                <span class="font-normal text-gray-500 dark:text-gray-400">· SQL {{ $fmt($registro->sqlMs) }} ({{ $registro->sqlQtd }} consultas) · PHP e demais {{ $fmt($phpMs) }} · memória {{ number_format($registro->memoriaMb, 1, ',', '.') }} MB</span>
            </p>
            <div class="flex h-3 w-full gap-0.5 overflow-hidden rounded" role="img" aria-label="SQL {{ round($percentualSql) }}% do tempo, PHP e demais {{ 100 - round($percentualSql) }}%">
                <div class="h-full rounded-s" style="width: {{ $percentualSql }}%; background: #2a78d6"></div>
                <div class="h-full flex-1 rounded-e" style="background: #eb6834"></div>
            </div>
            <div class="mt-1 flex gap-4 text-xs text-gray-600 dark:text-gray-300">
                <span><span class="inline-block h-2 w-2 rounded-sm" style="background: #2a78d6"></span> SQL {{ round($percentualSql) }}%</span>
                <span><span class="inline-block h-2 w-2 rounded-sm" style="background: #eb6834"></span> PHP e demais {{ 100 - round($percentualSql) }}%</span>
            </div>
        </div>

        @if ($registro->livewire !== [])
            <div>
                <p class="mb-1 font-medium text-gray-950 dark:text-white">Componentes Livewire</p>
                <ul class="list-disc ps-5 font-mono text-xs text-gray-700 dark:text-gray-200">
                    @foreach ($registro->livewire as $componente)
                        <li>{{ $componente }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($erro !== null)
            <div class="rounded-lg border border-danger-300 p-3 dark:border-danger-700">
                <p class="font-medium text-danger-700 dark:text-danger-400">{{ $erro['class'] }}</p>
                <p class="text-gray-700 dark:text-gray-200">{{ $erro['message'] }}</p>
                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $erro['file'] }}:{{ $erro['line'] }}</p>
            </div>
        @endif

        <div>
            <p class="mb-1 font-medium text-gray-950 dark:text-white">Consultas que mais gastaram tempo</p>
            @if ($consultas === [])
                <p class="text-gray-500 dark:text-gray-400">Nenhuma consulta SQL nesta requisição.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-1 pe-3">Vezes</th>
                                <th class="py-1 pe-3">Tempo</th>
                                <th class="py-1 pe-3">Conexão</th>
                                <th class="py-1">SQL (sem valores)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($consultas as $c)
                                <tr>
                                    <td @class(['py-1 pe-3 tabular-nums', 'font-semibold text-warning-700 dark:text-warning-400' => $c['count'] >= 20])>{{ $c['count'] }}</td>
                                    <td class="py-1 pe-3 tabular-nums">{{ $fmt($c['time_ms']) }}</td>
                                    <td class="py-1 pe-3">{{ $c['connection'] }}</td>
                                    <td class="py-1 font-mono break-all">{{ $c['SQL'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
