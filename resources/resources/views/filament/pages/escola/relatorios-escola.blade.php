<x-filament-panels::page>

    {{--
    Envolvemos o formulário em um componente Alpine.
    'formState' ficará sincronizado automaticamente com a variável $data do PHP.
    --}}
    <div x-data="{ formState: $wire.$entangle('data') }">

        <form method="POST" action="{{ route('reports.pedagogico.generate') }}" target="_blank" class="space-y-6">
            @csrf

            {{-- Renderiza o formulário visual do Filament --}}
            {{ $this->form }}

            {{--
            INPUTS OCULTOS COM ALPINE JS
            Usamos :value (atalho para x-bind:value) para pegar o valor de 'formState'
            em tempo real, sem precisar de roundtrip ao servidor.
            --}}

            {{-- Campos Simples --}}
            <input type="hidden" name="report_type" :value="formState.report_type">
            <input type="hidden" name="data_inicio" :value="formState.data_inicio">
            <input type="hidden" name="data_fim" :value="formState.data_fim">

            {{-- Toggle (Retorna true/false, convertemos para 1 ou 0) --}}
            <input type="hidden" name="ativo" :value="formState.ativo ? '1' : '0'">

            {{--
            Campo de Array (Cursos IDs)
            Verifica se é array e cria inputs para cada ID, ou um input simples se for único.
            --}}
            <template x-if="Array.isArray(formState.cursos_ids)">
                <template x-for="id in formState.cursos_ids" :key="id">
                    <input type="hidden" name="cursos_ids[]" :value="id">
                </template>
            </template>
            {{-- Fallback caso cursos_ids não seja array (select simples) --}}
            <template x-if="!Array.isArray(formState.cursos_ids) && formState.cursos_ids">
                <input type="hidden" name="cursos_ids[]" :value="formState.cursos_ids">
            </template>

            <div class="flex justify-end mt-4">
                <x-filament::button type="submit" icon="heroicon-o-printer">
                    Gerar Relatório
                </x-filament::button>
            </div>
        </form>

    </div>

</x-filament-panels::page>
