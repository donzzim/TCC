<x-filament-widgets::widget>
    @if ($contratacao)
        <x-filament::section class="bg-warning-50 border-warning-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-2 bg-warning-100 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-600" />
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-warning-800">Ação Necessária: Documentação de Contratação</h3>
                        <p class="text-sm text-warning-700 mt-1">
                            Você possui documentações e/ou termos pendentes para o curso
                            <strong>{{ $contratacao->curso->titulo }}</strong>.
                        </p>
                    </div>
                </div>

                <div class="flex-shrink-0 ml-4">
                    <x-filament::button
                        href="{{ route('filament.app.pages.minha-contratacao', ['id' => $contratacao->id]) }}"
                        tag="a" color="info" icon="heroicon-m-arrow-right-circle">
                        Preencher Agora
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @else
        <div class="hidden"></div>
    @endif
</x-filament-widgets::widget>
