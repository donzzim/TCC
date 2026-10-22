<div class="inline-flex items-center p-1 bg-white border rounded-lg shadow-sm border-slate-200" role="group" aria-label="Alternar visualização dos cursos">
    <button
        type="button"
        wire:click="setView('cards')"
        aria-pressed="{{ $view === 'cards' ? 'true' : 'false' }}"
        title="Visualizar em cards"
        class="inline-flex items-center justify-center w-9 h-9 rounded-md transition focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2 {{ $view === 'cards' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}"
    >
        <i data-lucide="layout-grid" class="w-4 h-4"></i>
        <span class="sr-only">Cards</span>
    </button>
    <button
        type="button"
        wire:click="setView('list')"
        aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
        title="Visualizar em lista"
        class="inline-flex items-center justify-center w-9 h-9 rounded-md transition focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2 {{ $view === 'list' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}"
    >
        <i data-lucide="list" class="w-4 h-4"></i>
        <span class="sr-only">Lista</span>
    </button>
</div>
