<section id="cursos" class="{{ $sectionClasses }}">
    @once
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        <script>
            document.addEventListener('livewire:init', () => {
                let lucideRefreshQueued = false;

                Livewire.hook('morph.updated', () => {
                    if (! window.lucide || lucideRefreshQueued) {
                        return;
                    }

                    lucideRefreshQueued = true;

                    window.requestAnimationFrame(() => {
                        window.lucide.createIcons();
                        lucideRefreshQueued = false;
                    });
                });
            });
        </script>
    @endonce

    <div class="{{ $innerClasses }}">
        @if($showHeading)
            <div class="flex flex-col gap-6 mb-10 lg:mb-14">
                <div class="text-center">
                    <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl">{{ $heading }}</h2>
                    @if($showAllLink)
                        <p class="text-center text-slate-500">
                            Todos os cursos
                            <a href="{{ route('portal.courses') }}" class="text-sm font-medium text-blue-900 hover:text-blue-700">
                                aqui &rarr;
                            </a>
                        </p>
                    @endif
                </div>

                <div class="flex flex-col items-stretch justify-end gap-3 sm:flex-row sm:items-center">
                    @include('templates.default.partials.course-search')
                    @include('templates.default.partials.course-view-toggle')
                    @include('templates.default.partials.course-sort')
                </div>
            </div>
        @else
            <div class="flex flex-col items-stretch justify-end gap-3 mb-8 sm:flex-row sm:items-center">
                @include('templates.default.partials.course-search')
                @include('templates.default.partials.course-view-toggle')
                @include('templates.default.partials.course-sort')
            </div>
        @endif

        @if($courses->count())
            @if($view === 'cards')
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($courses as $course)
                        @php
                            $courseUrl = route('portal.course', $course->uuid);
                            $arquivo = $course->arquivos->first()
                                ? asset("storage/{$course->arquivos->first()->arquivo}")
                                : asset('images/folder.png');
                        @endphp

                        <article wire:key="course-card-{{ $course->id }}" class="relative overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg group">
                            @if($course->modalidade_id == config('settings.modalidade.ead'))
                                <div class="absolute top-0 left-0 z-10">
                                    <div class="flex items-center justify-center bg-white/90 backdrop-blur-sm">
                                        <img src="{{ asset('images/moodle_logo.png') }}" alt="Plataforma Moodle"
                                            class="h-24 w-auto rounded-tl-lg opacity-25" title="Curso EaD Moodle">
                                    </div>
                                </div>
                            @endif

                            <a href="{{ $courseUrl }}" aria-label="Ver detalhes de {{ $course->titulo }}">
                                <img src="{{ $arquivo }}" alt="{{ $course->titulo }}" class="object-fill w-full h-[520px] lg:h-[600px]">
                            </a>

                            <div class="p-6">
                                <h3 class="mb-2 text-lg font-semibold text-slate-900">{{ $course->titulo }}</h3>
                                <p class="mb-4 text-sm text-slate-600 line-clamp-3">{!! $course->descricao !!}</p>
                                <a href="{{ $courseUrl }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-900 hover:text-blue-700">
                                    Leia mais
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="overflow-hidden bg-white border rounded-lg shadow-sm border-slate-200">
                    <div class="hidden px-5 py-3 text-xs font-semibold tracking-wide uppercase border-b md:flex md:items-center md:gap-5 bg-slate-100 text-slate-500 border-slate-200">
                        <div class="flex-none w-24 lg:w-28"></div>
                        <div class="flex-1 min-w-0">Curso</div>
                        <div class="grid flex-none w-80 grid-cols-3 gap-3 lg:w-96">
                            <div>Data</div>
                            <div>Modalidade</div>
                            <div>Carga</div>
                        </div>
                        <div class="flex-none w-24"></div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @foreach($courses as $course)
                            @php
                                $courseUrl = route('portal.course', $course->uuid);
                                $arquivo = $course->arquivos->first()
                                    ? asset("storage/{$course->arquivos->first()->arquivo}")
                                    : asset('images/folder.png');
                                $periodo = $course->inicio ? $course->inicio->format('d/m/Y') : 'A definir';
                                $modalidade = $course->modalidade->descricao ?? 'A definir';
                                $cargaHoraria = $course->carga_horaria ? "{$course->carga_horaria}h" : 'A definir';
                                $descricao = \Illuminate\Support\Str::limit(strip_tags((string) $course->descricao), 160);
                            @endphp

                            <a href="{{ $courseUrl }}" wire:key="course-list-{{ $course->id }}"
                                class="flex flex-col gap-4 px-5 py-5 transition group md:flex-row md:items-center md:gap-5 hover:bg-blue-50/70">
                                <img src="{{ $arquivo }}" alt="{{ $course->titulo }}"
                                    class="object-cover flex-none w-24 h-32 border rounded-md shadow-sm lg:w-28 lg:h-36 border-slate-200 bg-slate-100">

                                <div class="flex-1 min-w-0">
                                    <div class="text-base font-semibold leading-snug text-slate-900 group-hover:text-blue-900">{{ $course->titulo }}</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 line-clamp-3">{{ $descricao }}</p>
                                </div>

                                <div class="grid flex-none grid-cols-3 gap-3 text-sm text-slate-700 md:w-80 lg:w-96">
                                    <div><span class="block text-xs font-medium text-slate-500 md:hidden">Data</span>{{ $periodo }}</div>
                                    <div><span class="block text-xs font-medium text-slate-500 md:hidden">Modalidade</span>{{ $modalidade }}</div>
                                    <div><span class="block text-xs font-medium text-slate-500 md:hidden">Carga</span>{{ $cargaHoraria }}</div>
                                </div>

                                <div class="inline-flex items-center gap-1 text-sm font-medium text-blue-900 md:flex-none md:w-24 md:justify-end">
                                    Detalhes
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($hasMore)
                <div class="flex justify-center pt-8" x-data="{ busy: false }"
                    x-init="
                        const observer = new IntersectionObserver((entries) => {
                            if (! entries.some((entry) => entry.isIntersecting) || busy) return;
                            busy = true;
                            observer.disconnect();
                            $wire.loadMore().finally(() => busy = false);
                        }, { rootMargin: '420px' });
                        observer.observe($el);
                    ">
                    <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium transition bg-white border rounded-md text-slate-700 border-slate-200 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                        <i data-lucide="loader-circle" class="w-4 h-4"></i>
                        Carregar mais
                    </button>
                </div>
            @endif
        @else
            <p class="text-center text-slate-500">
                {{ trim($search) !== '' ? 'Nenhum curso encontrado para a pesquisa.' : $emptyMessage }}
            </p>
        @endif
    </div>
</section>
