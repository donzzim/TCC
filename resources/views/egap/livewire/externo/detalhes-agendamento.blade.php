@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Agendamento\Recursos> $recursos */
@endphp

<div class="space-y-4">
    @forelse ($recursos as $recurso)
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Condutor</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $recurso->condutorRef?->idPessoaRef?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Contato do condutor</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $recurso->condutorRef?->contato ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Veículo</dt>
                    <dd class="text-gray-600 dark:text-gray-300">
                        {{ $recurso->veiculoRef?->descricao ?? '-' }}
                        @if ($recurso->veiculoRef?->placa)
                            / {{ $recurso->veiculoRef->placa }}
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-semibold text-gray-900 dark:text-white">Observação</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $recurso->observacao ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum motorista/veículo vinculado a este agendamento ainda.</p>
    @endforelse
</div>
