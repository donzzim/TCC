<section id="cursos" class="py-20 bg-slate-50">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
            <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl">Ações Disponíveis</h2>
            <p class="text-center md:col-span-3 text-slate-500">Todas as ações
                <a href="{{ route('portal.courses') }}"
                    class="text-sm font-medium text-blue-900 hover:text-blue-700">aqui →</a>
            </p>
        </div>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @forelse($courses as $course)
                @php
                    $arquivo = './images/folder.png';
                    if(isset($course) && $course->arquivos->first()) {
                        $arquivo = asset("storage/{$course->arquivos->first()->arquivo}");
                    }
                @endphp

                <div class="relative overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg group">

                    @if($course->modalidade_id == config('settings.modalidade.ead'))
                        <div class="absolute top-0 left-0 z-10">
                            <div class="flex items-center justify-center bg-white/90 backdrop-blur-sm">
                                <img src="{{ asset('images/moodle_logo.png') }}"
                                     alt="Plataforma Moodle"
                                     class="h-24 w-auto rounded-tl-lg opacity-25"
                                     title="Curso EaD Moodle">
                            </div>
                        </div>
                    @endif
                    {{-- FIM LÓGICA --}}

                    @if($arquivo)
                        <a href="#">
                            <img src="{{ $arquivo }}" alt="{{ $course->titulo }}"
                                class="aspect-video object-fill w-[500px] h-[600px]">
                        </a>
                    @else
                        <div class="aspect-video bg-gradient-to-br from-blue-100 to-blue-200"></div>
                    @endif

                    <div class="p-6">
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">{{ $course->titulo }}</h3>
                        <p class="mb-4 text-sm text-slate-600 line-clamp-3">{!! $course->descricao !!}</p>
                        <a href="{{ route('portal.course', $course->uuid) }}"
                            class="text-sm font-medium text-blue-900 hover:text-blue-700">Leia mais →</a>
                    </div>
                </div>
            @empty
                <p class="text-center md:col-span-3 text-slate-500">Nenhum curso disponível no momento.</p>
            @endforelse
        </div>
    </div>
</section>
