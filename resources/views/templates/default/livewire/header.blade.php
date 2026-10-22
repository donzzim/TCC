<header class="sticky top-0 z-50 border-b bg-white/95 backdrop-blur">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('portal.home') }}" class="flex items-center space-x-4">
                @if ($settings && $settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}" alt="{{ $settings->brand_name }}" class="h-10">
                @else
                    <i data-lucide="scale" class="w-8 h-8 text-sky-900"></i>
                @endif
                <div>
                    <h1 class="text-lg font-bold text-sky-900">{{ $settings->brand_name ?? 'EMES' }}</h1>
                    <p class="text-xs text-slate-600">{{ $settings->name }}</p>
                </div>
            </a>

            <div class="flex items-center space-x-4">
                <form action="{{ route('portal.search') }}" method="GET" class="relative hidden md:block">
                    <input type="search" name="q" placeholder="Pesquisar..." value="{{ request('q') }}"
                        class="w-auto px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-1 focus:ring-sky-100 focus:w-64 transition-all duration-300">
                    <button type="submit"
                        class="absolute top-0 bottom-0 right-0 px-3 text-slate-500 hover:text-sky-700">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>
                </form>

                <div class="hidden md:flex">
                    <livewire:portal.navigation menuName="Principal" />
                </div>
            </div>

            <button id="mobile-menu-btn" class="p-2 md:hidden">
                <i data-lucide="menu" class="w-6 h-6 text-slate-700"></i>
            </button>
        </div>
        <div id="mobile-menu" class="hidden py-4 border-t md:hidden">
            <livewire:portal.navigation menuName="Principal" />
        </div>
    </div>
</header>
