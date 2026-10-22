<label class="relative block w-full sm:w-80">
    <span class="sr-only">Pesquisar curso</span>
    <i data-lucide="search" class="absolute w-4 h-4 -translate-y-1/2 left-3 top-1/2 text-slate-400"></i>
    <input
        type="text"
        wire:model.live.debounce.350ms="search"
        placeholder="Pesquisar curso"
        class="w-full h-11 rounded-lg border-slate-200 bg-white py-2 pl-10 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-700 focus:ring-blue-700"
    >
    @if(trim($search) !== '')
        <button
            type="button"
            wire:click="clearSearch"
            title="Limpar pesquisa"
            class="absolute inline-flex items-center justify-center w-7 h-7 transition -translate-y-1/2 rounded-md right-2 top-1/2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-700"
        >
            <i data-lucide="x" class="w-4 h-4"></i>
            <span class="sr-only">Limpar pesquisa</span>
        </button>
    @endif
</label>
