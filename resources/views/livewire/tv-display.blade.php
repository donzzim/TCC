<div class="fixed inset-0 bg-gray-900 overflow-hidden"
    x-data="{ currentSlide: 0, totalSlides: {{ $data['carousel']->count() }}, slideInterval: 10000 }" x-init="
        // Inicia o carrossel automático
        setInterval(() => {
            currentSlide = (currentSlide + 1) % totalSlides;
        }, slideInterval);

        // O Livewire se encarrega da atualização de dados via wire:poll,
        // mas o ciclo de slides deve ser mantido no X-Data.
    " wire:poll.10s="loadContent" {{-- Atualiza o conteúdo a cada 10 segundos --}}>

    <main class="absolute h-full w-full flex flex-row mb-18">
        <div class="w-1/3 h-auto aspect-video overflow-hidden border-4 border-white z-50 p-2">
            <video class="w-auto h-auto object-cover rounded-lg" src="{{ asset('images/video_institucional.mp4') }}"
                autoplay loop muted playsinline preload="auto">
                Seu navegador não suporta a tag de vídeo.
            </video>
        </div>

        @if($data['carousel']->isNotEmpty())
        <div class="h-full w-full bg-white p-2 rounded-lg">
            @foreach($data['carousel'] as $index => $item)

            <div x-show="currentSlide === {{ $index }}" x-transition:enter="transition-opacity ease-out duration-1000"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-500" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="inset-0 bg-cover w-full h-full bg-center flex items-end justify-center p-12 transition-all duration-1000"
                style="background-image: url('{{ $item->image_url ?? 'https://placehold.co/1920x1080/0f172a/94a3b8?text=EMES' }}');">

                <div class="absolute inset-0 bg-black/50 backdrop-brightness-75"></div>

                <div
                    class="relative max-w-5xl text-center text-white px-1 pb-6 rounded-xl bg-gray-900/70 shadow-2xl backdrop-blur-sm transform transition-all duration-500 ease-in-out">
                    <span class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-blue-400">
                        {{ property_exists($item, 'link') ? 'Notícia em Destaque' : 'Novo Curso' }}
                    </span>
                    <h2 class="mt-2 text-xl sm:text-2xl md:text-3xl font-extrabold leading-tight">
                        {{ $item->title }}
                    </h2>
                    <p class="mt-4 text-sm sm:text-lg text-gray-200 line-clamp-3">
                        {!! $item->excerpt !!}
                    </p>
                    <a href="{{ $item->link ?? '#' }}" target="_blank"
                        class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white font-bold rounded-full hover:bg-blue-700 transition duration-300 text-base shadow-lg hidden sm:inline-block">
                        Ver Detalhes →
                    </a>
                </div>

                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                    @foreach($data['carousel'] as $i => $s)
                    <div :class="{ 'bg-blue-500 w-6': currentSlide === {{ $i }}, 'bg-gray-400 w-2': currentSlide !== {{ $i }} }"
                        class="h-2 rounded-full transition-all duration-300"></div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </main>

    <footer
        class="absolute bottom-0 left-0 right-0 h-16 bg-blue-800 text-white shadow-2xl overflow-hidden flex items-center z-50">
        <div class="w-full relative h-full flex items-center">
            {{-- Estilo para animação de rolagem com Tailwind/CSS --}}
            <style>
                @keyframes marquee {
                    0% {
                        transform: translateX(100%);
                    }

                    100% {
                        transform: translateX(-100%);
                    }
                }

                .marquee-content {
                    white-space: nowrap;
                    padding-left: 100%;
                    /* Inicia o texto fora da tela */
                    animation: marquee 100s linear infinite;
                    /* 40s de duração, linear, infinito */
                    display: inline-block;
                    font-weight: 700;
                    font-size: 1.25rem;
                    /* text-xl */
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.4);
                }
            </style>
            <div class="marquee-content">
                {{ $footerMarqueeText }} &nbsp; - &nbsp; {{ $footerMarqueeText }} &nbsp; - &nbsp;
                {{ $footerMarqueeText }} &nbsp; - &nbsp; {{ $footerMarqueeText }}
            </div>
        </div>
    </footer>
</div>