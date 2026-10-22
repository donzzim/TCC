<x-filament-panels::page>

    <div>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">Kanban</h1>
            {{ $this->createListAction }}
        </div>
        <div class="flex pb-1 space-x-1 overflow-x-auto flex-nowrap custom-scrollbar">
            @foreach ($lists as $list)

            <div class="flex-shrink-0 w-80">
                <x-filament::section :heading="$list->descricao"
                    class="relative flex flex-col h-full shadow-lg rounded-xl"
                    style="border-top: 3px solid {{ $list->cor }};">

                    <button wire:click='mountAction("editDemanda", @json(["id" => $list->id]))' title="Editar Demanda"
                        class="absolute top-4 right-4 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 group-hover:opacity-100 transition-opacity duration-300">
                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                    </button>

                    <div class="flex-1 p-1 -mx-1 overflow-y-auto">
                        @foreach ($list->cards as $card)

                        <x-filament::card class="flex flex-col rounded-lg shadow-md cursor-grab active:cursor-grabbing"
                            style="border-left: 4px solid {{ $card->cor }}; background-color: {{ $card->cor }}1A;">

                            <div class="flex items-center justify-between text-sm py-2">

                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $card->descricao }}</p>

                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot name="trigger">
                                        <x-filament::icon-button icon="heroicon-o-ellipsis-horizontal-circle"
                                            label="Opções do Card" size="sm" color="gray" />
                                    </x-slot>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.list.item wire:click="mountAction('createCard', {{ json_encode(['list_id' =>
                                            $card->list_id]) }})" icon="heroicon-o-plus-circle">
                                            Novo Card
                                        </x-filament::dropdown.list.item>

                                        <x-filament::dropdown.list.item
                                            wire:click="mountAction('editCard', {{ json_encode(['id'=> $card->id])}})"
                                            icon="heroicon-o-pencil">
                                            Editar Card
                                        </x-filament::dropdown.list.item>

                                        <x-filament::dropdown.list.item
                                            wire:click="mountAction('createTask', {{ json_encode(['card_id' => $card->id]) }})"
                                            icon="heroicon-o-plus">
                                            Nova Task
                                        </x-filament::dropdown.list.item>
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>


                            <div class="space-y-1">
                                @foreach ($card->tasks as $task)
                                <div
                                    class="relative flex items-center p-0 text-xs text-gray-700 rounded-md shadow-sm dark:text-gray-300 dark:bg-gray-900">

                                    <span class="flex-shrink-0 w-2 h-2 mx-2 my-1 rounded-full"
                                        style="background-color: {{ $card->cor }};"></span>

                                    <p class="flex-1 p-1 my-1">{{ $task->descricao }}</p>

                                    <button wire:click='mountAction("editTask", @json(["id" => $task->id]))'
                                        title="Editar Task"
                                        class="absolute top-3 right-4 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 group-hover:opacity-100 transition-opacity duration-300">
                                        <x-heroicon-o-pencil class="w-4 h-4" />
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </x-filament::card>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
            @endforeach
        </div>
    </div>

</x-filament-panels::page>