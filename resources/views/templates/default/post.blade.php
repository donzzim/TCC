@extends('templates.default.layouts.app')

@section('content')
    <style>
        .post h2 {
            @apply text-2xl font-bold mt-8 mb-4 text-gray-800;
        }

        .post h3 {
            @apply text-xl font-bold mt-6 mb-3 text-gray-800;
        }

        .post p {
            @apply mb-4 leading-relaxed;
        }

        .post ul {
            @apply list-disc list-inside mb-4 pl-4 space-y-2;
        }

        .post ol {
            @apply list-decimal list-inside mb-4 pl-4 space-y-2;
        }

        .post a {
            @apply text-sky-700 underline hover: text-sky-900 transition-colors;
        }

        .post blockquote {
            @apply border-l-4 border-gray-200 pl-4 py-2 my-4 italic text-gray-600;
        }

        .post strong {
            @apply font-semibold text-gray-800;
        }
    </style>

    <section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="container relative px-4 py-10 mx-auto sm:px-6 lg:px-8 lg:py-14">
            <div class="max-w-4xl">
                <span class="inline-block px-3 py-1 mb-6 text-sm text-white border rounded-full bg-white/20 border-white/30">
                    EMES - Escola da Magistratura do Estado do Espírito Santo
                </span>
                <h1 class="mb-6 text-4xl font-bold leading-tight lg:text-6xl">
                    {{ $post->title }}
                </h1>
                <p class="mb-4 text-xl leading-relaxed text-blue-100 lg:text-2xl">
                    {{ $post->title }}
                </p>
            </div>
        </div>
    </section>

    <div class="bg-white px-6 py-16 lg:px-8">
        <div class="mx-auto max-w-4xl text-base leading-7 text-gray-700">

            <div class="mb-8">
                <a href="{{ route('portal.posts.index') }}"
                    class="inline-flex items-center text-sm font-medium text-sky-700 transition-colors hover:text-sky-900">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Voltar para todas as notícias
                </a>
            </div>

            <header>
                @if ($post->image_path)
                    <img src="{{ Storage::url($post->image_path) }}" alt="{{ $post->title }}"
                        class="object-cover w-full h-auto transition-transform duration-300 group-hover:scale-105 rounded-2xl">
                @endif

                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    {{ $post->title }}
                </h1>

                <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                    @if ($post->atualizado)
                        <div class="flex items-center space-x-2">
                            <i data-lucide="user-circle" class="h-5 w-5"></i>
                            <span>{{ $post->atualizado->name }}</span>
                        </div>
                    @endif
                    <div class="flex items-center space-x-2">
                        <i data-lucide="calendar" class="h-5 w-5"></i>
                        <time datetime="{{ $post->updated_at->format('H:i:s') }}">
                            Atualizado em {{ $post->updated_at->format('d/m/Y') }}
                        </time>
                    </div>
                </div>

                <hr class="my-8">
            </header>

            <article class="post">
                {!! $post->content !!}
            </article>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            lucide.createIcons();
        });
    </script>
@endsection
