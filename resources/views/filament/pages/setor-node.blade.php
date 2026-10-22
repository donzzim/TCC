@php
    $isRoot = $isRoot ?? false;
    $descendantUserIds = collect();

    $fetchDescendants = function ($currentSetor) use (&$fetchDescendants, &$descendantUserIds): void {
        foreach ($currentSetor->filhos as $filho) {
            $descendantUserIds = $descendantUserIds->merge($filho->setores->pluck('id'));
            $fetchDescendants($filho);
        }
    };

    $fetchDescendants($setor);
    $usuariosExclusivos = $setor->setores->whereNotIn('id', $descendantUserIds->unique());
@endphp

<li>
    <div @class([
        'relative z-10 flex min-w-[220px] max-w-[320px] flex-col rounded-lg p-4 text-center shadow-md',
        'border-0 bg-blue-600 text-white' => $isRoot,
        'border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800' => ! $isRoot,
    ])>
        <div class="mb-2 flex items-center justify-center gap-2">
            <button
                type="button"
                wire:click="mountAction('editSetor', { setor_id: {{ $setor->id }} })"
                class="font-bold leading-tight transition hover:underline focus:outline-none {{ $isRoot ? 'text-2xl' : 'text-sm text-gray-900 dark:text-white' }}"
                title="Editar setor"
            >
                {{ $setor->setor ?: 'Setor sem nome' }}
            </button>

            <span class="inline-flex items-center justify-center rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                {{ $usuariosExclusivos->count() }}
            </span>
        </div>

        @if($usuariosExclusivos->isNotEmpty())
            <div class="mb-4 mt-2 flex flex-col gap-2 text-left">
                @foreach($usuariosExclusivos as $user)
                    <button
                        type="button"
                        wire:click="mountAction('editUsuario', { user_id: {{ $user->id }} })"
                        class="flex w-full flex-col gap-0.5 rounded-md border border-gray-100 bg-white p-2 text-left shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
                        title="Editar lotação"
                    >
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <x-filament::icon icon="heroicon-m-user" class="h-4 w-4 shrink-0 text-primary-500" />
                            <span class="truncate">{{ $user->name }}</span>
                        </span>
                        <span class="ml-6 truncate text-[10px] text-gray-500 dark:text-gray-400">
                            {{ $user->perfil?->cargo ?: 'Cargo não informado' }}
                        </span>
                    </button>
                @endforeach
            </div>
        @else
            <span class="mb-4 mt-2 text-xs italic text-gray-400">Sem usuários lotados</span>
        @endif

        <div class="mt-auto flex justify-center gap-4 border-t border-gray-200 pt-3 dark:border-gray-700">
            <button
                type="button"
                wire:click="mountAction('addUsuarioLotacao', { setor_id: {{ $setor->id }} })"
                class="text-gray-500 transition hover:text-primary-600"
                title="Lotar usuário"
            >
                <x-filament::icon icon="heroicon-m-user-plus" class="h-5 w-5" />
            </button>
            <button
                type="button"
                wire:click="mountAction('addSetorFilho', { parent_id: {{ $setor->id }} })"
                class="text-gray-500 transition hover:text-success-600"
                title="Adicionar sub-setor"
            >
                <x-filament::icon icon="heroicon-m-folder-plus" class="h-5 w-5" />
            </button>
        </div>
    </div>

    @if($setor->filhos->isNotEmpty())
        <ul>
            @foreach($setor->filhos as $filho)
                @include('filament.pages.setor-node', ['setor' => $filho, 'isRoot' => false])
            @endforeach
        </ul>
    @endif
</li>
