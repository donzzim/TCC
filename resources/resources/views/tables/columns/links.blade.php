<div class="flex items-center w-full space-x-2">
    <div class="flex-1 block">
        @if($getRecord()->cursos->count())
        <x-filament::icon-button
            :href="'/admin/cursos'"
            color="danger"
             badge-color="danger"
            label="Cursos"
            icon="heroicon-s-book-open"
            class="border border-red-100"
        >
            <x-slot name="badge">
                {{ $getRecord()->cursos->count() }}
            </x-slot>
        </x-filament::icon-button>
        @endif
    </div>

    <div class="flex-1 block">
        @if($getRecord()->tarefas->count())
        <x-filament::icon-button
            :href="'/admin/tarefas'"
            color="warning"
            badge-color="warning"
            label="Cursos"
            icon="heroicon-s-queue-list"
            class="border border-yellow-300"
        >
            <x-slot name="badge">
                {{ $getRecord()->tarefas->count() }}
            </x-slot>
        </x-filament::icon-button>
        @endif
    </div>
</div>
