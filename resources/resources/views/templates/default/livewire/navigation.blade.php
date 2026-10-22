<nav class="items-center space-x-1 md:flex">
    @foreach ($items as $item)

    @if($item->children->isNotEmpty())
    <div x-data="{ open: false }" @click.away="open = false" class="relative">
        <button @click="open = !open"
            class="inline-flex items-center px-1 py-2 text-sm font-medium transition-colors rounded-md text-slate-700 hover:bg-slate-100 hover:text-blue-900">
            <span>{{ $item->label }}</span>
            <i data-lucide="chevron-down" class="w-4 h-4 ml-1 transition-transform duration-200"
                :class="{'rotate-180': open}"></i>
        </button>

        <div x-show="open" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-10 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            style="display: none;">
            <div class="py-1">
                @foreach($item->children as $child)
                <a href="{{ url($child->url) }}"
                    class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 hover:text-blue-900"
                    @if($child->open_in_new_tab) target="_blank" @endif>
                    {{ $child->label }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @else

    <a href="{{ url($item->url) }}"
        class="px-1 py-2 text-sm font-medium transition-colors rounded-md text-slate-700 hover:bg-slate-100 hover:text-blue-900"
        @if($item->open_in_new_tab) target="_blank" @endif>
        {{ $item->label }}
    </a>
    @endif

    @endforeach

    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-900 rounded-md hover:bg-blue-800">
            Acesse Aqui
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </button>

        <div x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                style="display: none;">
            <div class="py-1">
                <a href="https://www.tjes.jus.br/emes/app" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-900">
                    Portal do Aluno
                </a>
                <a href="https://sistemas.tjes.jus.br/ead/login/index.php" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-900">
                    Portal do Aluno (EaD)
                </a>
                <a href="https://www.tjes.jus.br/emes/admin" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-900">
                    Área Administrativa
                </a>
            </div>
        </div>
    </div>
</nav>
