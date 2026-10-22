<section id="noticias" class="py-20 bg-gray-50"
    x-data="{
        activeSlide: 0,
        totalSlides: {{ ceil($posts->count() / 3) }},
        autoplayInterval: null,
        init() {
            this.startAutoplay();
        },
        startAutoplay() {
            this.autoplayInterval = setInterval(() => {
                this.next();
            }, 6000); // 6 segundos para dar tempo de ler as 3 notícias
        },
        stopAutoplay() {
            clearInterval(this.autoplayInterval);
        },
        next() {
            this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
        },
        prev() {
            this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides;
        }
    }"
    @mouseenter="stopAutoplay()"
    @mouseleave="startAutoplay()">

    <div class="container px-4 mx-auto sm:px-6 lg:px-8">

        {{-- Cabeçalho com Navegação --}}
        <div class="mb-12 flex flex-col sm:flex-row justify-between items-end gap-4">
            <div class="text-center sm:text-left w-full sm:w-auto">
                <h2 class="text-3xl font-bold text-slate-900 lg:text-4xl">Notícias</h2>
                <p class="mt-2 text-slate-600">Acompanhe as últimas atualizações.</p>
            </div>

            {{-- Botões de Navegação --}}
            @if($posts->count() > 3)
            <div class="hidden sm:flex gap-2">
                <button @click="prev()"
                        class="p-2 rounded-full hover:bg-white hover:shadow-md hover:text-blue-700 transition bg-transparent text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <button @click="next()"
                        class="p-2 rounded-full hover:bg-white hover:shadow-md hover:text-blue-700 transition bg-transparent text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>
            @endif
        </div>

        {{-- Área do Slider (Janela de Visualização) --}}
        <div class="overflow-hidden relative">

            {{-- Trilha do Slider (Move-se com transform) --}}
            <div class="flex transition-transform duration-700 ease-in-out"
                 :style="'transform: translateX(-' + (activeSlide * 100) + '%)'">

                {{--
                    LOOP DOS GRUPOS (CHUNKS)
                    Aqui dividimos os posts em pedaços de 3.
                    Cada pedaço é um "Slide" que ocupa 100% da largura.
                --}}
                @foreach($posts->chunk(3) as $group)
                    <div class="w-full flex-shrink-0 px-1">
                        {{-- Grid interno de 3 colunas --}}
                        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">

                            @foreach($group as $post)
                                <div class="flex flex-col overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg h-full">
                                    @if($post->image_path)
                                    <a href="{{ route('portal.post', $post->slug) }}" class="block overflow-hidden h-56">
                                        <img src="{{ Storage::url($post->image_path) }}"
                                            alt="{{ $post->title }}"
                                            class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                                    </a>
                                    @else
                                    <div class="h-56 bg-gradient-to-br from-blue-100 to-blue-200"></div>
                                    @endif

                                    <div class="p-6 flex flex-col flex-1">
                                        <h3 class="mb-2 text-lg font-semibold text-slate-900 line-clamp-2">
                                            {{ $post->title }}
                                        </h3>
                                        <p class="mb-4 text-sm text-slate-600 line-clamp-3 flex-1">
                                            {{ $post->excerpt }}
                                        </p>
                                        <div class="mt-auto">
                                            <a href="{{ route('portal.post', $post->slug) }}"
                                                class="text-sm font-medium text-blue-900 hover:text-blue-700 inline-flex items-center">
                                                Leia mais
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Preenchimento vazio se o último grupo tiver menos de 3 posts --}}
                            @if($group->count() < 3)
                                @for($i = 0; $i < (3 - $group->count()); $i++)
                                    <div class="hidden lg:block"></div> {{-- Espaço vazio para manter o grid alinhado --}}
                                @endfor
                            @endif

                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- Paginação (Bolinhas) --}}
        @if($posts->count() > 3)
        <div class="flex justify-center mt-8 gap-2">
            <template x-for="slide in totalSlides">
                <button @click="activeSlide = slide - 1"
                        class="w-3 h-3 rounded-full transition-all duration-300"
                        :class="activeSlide === (slide - 1) ? 'bg-blue-600 w-6' : 'bg-gray-300 hover:bg-gray-400'">
                </button>
            </template>
        </div>
        @endif

    </div>
</section>
