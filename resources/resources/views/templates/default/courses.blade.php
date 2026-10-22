@extends('templates.default.layouts.app')

@section('content')

<section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
    <div class="absolute inset-0 bg-black/20"></div>
    <div class="container relative px-4 py-10 mx-auto sm:px-6 lg:px-8 lg:py-14">
        <div class="max-w-4xl">
            <span
                class="inline-block px-3 py-1 mb-6 text-sm text-white border rounded-full bg-white/20 border-white/30">
                EMES - Escola da Magistratura do Estado do Espírito Santo
            </span>
            <h1 class="mb-6 text-4xl font-bold leading-tight lg:text-6xl">
                Ações Disponíveis
            </h1>
            <p class="mb-4 text-xl leading-relaxed text-blue-100 lg:text-2xl">
                Ações Disponíveis
            </p>
        </div>
    </div>
</section>

<div class="bg-slate-50">
    <div class="container mx-auto px-4 py-16 lg:px-8">
        {{-- Título da Página --}}
        <header class="mb-12 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Ações Disponíveis
            </h1>
        </header>

        @if($courses->count())
        <section id="cursos" class="py-20 bg-slate-50">
            <div class="container px-4 mx-auto sm:px-6 lg:px-8">
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

        @else
        <p class="text-center text-gray-500">Nenhuma ação encontrada no momento.</p>
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        lucide.createIcons();
    });
</script>
@endsection
