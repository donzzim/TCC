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
                Pesquisa
            </h1>
            <p class="mb-4 text-xl leading-relaxed text-blue-100 lg:text-2xl">
                Pesquisa de conteúdo no site
            </p>
        </div>
    </div>
</section>

<div class="bg-slate-50">
    <div class="container mx-auto px-4 py-16 lg:px-8">
        {{-- Cabeçalho da página de resultados --}}
        <header class="mb-12">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Resultados da busca por: "{{ $query }}"
            </h1>
            <p class="mt-2 text-lg text-gray-600">
                {{ $results->count() }} {{ Str::plural('resultado', $results->count()) }} encontrado(s).
            </p>
        </header>

        {{-- Lista de Resultados --}}
        @if($results->isNotEmpty())
        <div class="space-y-8">
            @foreach($results as $result)
            <div class="p-6 bg-white rounded-lg shadow-md">
                {{-- Badge do Tipo (Página ou Notícia) --}}
                <span class="inline-block px-2 py-1 mb-2 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">
                    {{ $result->type }}
                </span>

                {{-- Título do Resultado --}}
                <h2 class="mb-2 text-xl font-semibold text-gray-900">
                    <a href="{{ url($result->url) }}" class="hover:text-blue-700">
                        {{ $result->title }}
                    </a>
                </h2>

                {{-- Resumo / Excerpt --}}
                <p class="text-sm text-gray-600 line-clamp-2">
                    {{ $result->excerpt }}
                </p>
            </div>
            @endforeach
        </div>
        @else
        {{-- Mensagem para quando não há resultados --}}
        <div class="text-center py-16">
            <i data-lucide="search-x" class="w-16 h-16 mx-auto text-gray-400"></i>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Nenhum resultado encontrado</h3>
            <p class="mt-1 text-sm text-gray-500">Tente buscar por um termo diferente.</p>
        </div>
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        lucide.createIcons();
    });
</script>
@endsection