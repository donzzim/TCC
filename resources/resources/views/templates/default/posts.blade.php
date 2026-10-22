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
                Todas as Notícias
            </h1>
            <p class="mb-4 text-xl leading-relaxed text-blue-100 lg:text-2xl">
                Todas as Notícias
            </p>
        </div>
    </div>
</section>

<div class="bg-slate-50">
    <div class="container mx-auto px-4 py-16 lg:px-8">
        {{-- Título da Página --}}
        <header class="mb-12 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Todas as Notícias
            </h1>
            <p class="mt-2 text-lg text-gray-600">Fique por dentro das últimas atualizações e comunicados.</p>
        </header>

        {{-- Grid de Posts --}}
        @if($posts->count())
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($posts as $post)
            <article
                class="flex flex-col overflow-hidden transition-shadow duration-300 bg-white rounded-lg shadow-md group hover:shadow-xl">
                <a href="{{ route('portal.post', $post->slug) }}" class="block">
                    {{-- Imagem do Card --}}
                    @if($post->image_path)
                    <img src="{{ Storage::url($post->image_path) }}" alt="{{ $post->title }}"
                        class="object-cover w-full h-48 transition-transform duration-300 group-hover:scale-105">
                    @else
                    <div
                        class="flex items-center justify-center w-full h-48 bg-gradient-to-br from-blue-100 to-blue-200">
                        <i data-lucide="image-off" class="w-12 h-12 text-gray-400"></i>
                    </div>
                    @endif
                </a>

                <div class="flex flex-col flex-1 p-6">
                    {{-- Título e Link --}}
                    <h2 class="mb-2 text-lg font-semibold text-gray-900">
                        <a href="{{ route('portal.post', $post->slug) }}" class="hover:text-blue-700">
                            {{ $post->title }}
                        </a>
                    </h2>

                    {{-- Resumo --}}
                    <p class="flex-1 text-sm text-gray-600 line-clamp-3">
                        {{ $post->excerpt }}
                    </p>

                    {{-- Data de Publicação --}}
                    <div class="mt-4 text-xs text-gray-500">
                        Publicado em {{ $post->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        <div class="mt-12">
            {{ $posts->links('partials.pagination') }}
        </div>
        @else
        <p class="text-center text-gray-500">Nenhuma notícia encontrada no momento.</p>
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        lucide.createIcons();
    });
</script>
@endsection