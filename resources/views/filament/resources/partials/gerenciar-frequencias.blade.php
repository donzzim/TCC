<div class="space-y-4">
    @if ($temAtividades)
        <div class="space-y-2">
            @foreach ($dados as $atividade)
                <details
                    class="group border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-gray-900 open:ring-1 open:ring-primary-500">
                    <summary
                        class="flex items-center justify-between p-4 cursor-pointer font-medium text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-white/5 select-none">
                        <span>{{ $atividade['titulo'] }}</span>
                        <span class="transition group-open:rotate-180">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </summary>

                    <div class="p-4 border-t border-gray-200 dark:border-white/10">
                        @include('filament.resources.partials.lista-alunos', [
                            'alunos' => $atividade['alunos'],
                            'contexto' => 'atividade',
                        ])
                    </div>
                </details>
            @endforeach
        </div>
    @else
        <div class="border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-gray-900 p-4">
            @foreach ($dados as $atividade)
                <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-gray-100">{{ $titulo }}</h3>
                @include('filament.resources.partials.lista-alunos', [
                    'alunos' => $atividade['alunos'],
                    'contexto' => 'curso',
                ])
            @endforeach
        </div>
    @endif
</div>
