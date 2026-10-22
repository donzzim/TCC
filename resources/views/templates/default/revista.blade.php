@extends('templates.default.layouts.app')

@section('content')
    <section class="relative bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 text-white">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="container relative mx-auto px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <span class="mb-5 inline-block rounded-full border border-white/30 bg-white/20 px-3 py-1 text-sm">
                EMES - Escola da Magistratura do Estado do Espírito Santo
            </span>
            <h1 class="max-w-4xl text-4xl font-bold leading-tight lg:text-6xl">
                {{ $page->title }}
            </h1>
            @if ($page->seo_description)
                <p class="mt-4 max-w-3xl text-xl leading-relaxed text-blue-100 lg:text-2xl">
                    {{ $page->seo_description }}
                </p>
            @endif
        </div>
    </section>

    <div class="bg-white px-6 py-12 lg:px-8 lg:py-16">
        <div class="mx-auto grid w-full max-w-7xl gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <main class="min-w-0 text-base leading-7 text-gray-700">
                <header>
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        {{ $page->title }}
                    </h2>
                    <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        @if ($page->atualizado)
                            <span>Atualizado por {{ $page->atualizado->name }}</span>
                        @endif
                        @if ($page->updated_at)
                            <time datetime="{{ $page->updated_at->format('Y-m-d') }}">
                                {{ $page->updated_at->format('d/m/Y') }}
                            </time>
                        @endif
                    </div>
                    <hr class="my-8">
                </header>

                <article class="page-content">
                    {!! $page->content !!}
                </article>
            </main>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Publicações</h2>
                                @if ($articles)
                                    <p class="text-xs text-gray-500">
                                        {{ $articles->total() }} registro{{ $articles->total() === 1 ? '' : 's' }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <form method="GET" action="{{ url()->current() }}" class="relative mt-4">
                            <input
                                type="search"
                                name="publicacao_busca"
                                value="{{ $publicationSearch ?? request('publicacao_busca') }}"
                                placeholder="Pesquisar publicações"
                                class="h-10 w-full rounded-md border border-gray-300 bg-white px-3 pr-10 text-sm text-gray-900 outline-none transition focus:border-blue-700 focus:ring-2 focus:ring-blue-100"
                            >
                            <button type="submit" class="sr-only">Pesquisar</button>
                        </form>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($articles ?? [] as $article)
                            <a href="{{ $article->pdf_url }}" target="_blank" rel="noopener"
                                class="group block px-5 py-4 transition hover:bg-blue-50/60">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold leading-6 text-gray-950 group-hover:text-blue-800">
                                        {{ $article->title }}
                                    </h3>
                                    <span class="shrink-0 text-xs text-blue-700">PDF</span>
                                </div>

                                @if ($article->author)
                                    <p class="mt-2 text-sm text-gray-600">{{ $article->author }}</p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-1.5 text-[11px] font-medium uppercase">
                                    <span class="rounded border border-blue-100 bg-blue-50 px-2 py-1 text-blue-800">
                                        {{ $article->publication_type_label }}
                                    </span>
                                    @if ($article->volume)
                                        <span class="rounded border border-gray-200 px-2 py-1 text-gray-600">
                                            Vol. {{ $article->volume }}
                                        </span>
                                    @endif
                                    @if ($article->month_name || $article->year)
                                        <span class="rounded border border-gray-200 px-2 py-1 text-gray-600">
                                            {{ $article->month_name }}{{ $article->month_name && $article->year ? '/' : '' }}{{ $article->year }}
                                        </span>
                                    @endif
                                    @if ($article->pages)
                                        <span class="rounded border border-gray-200 px-2 py-1 text-gray-600">
                                            p. {{ $article->pages }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <p class="text-sm font-medium text-gray-800">Nenhuma publicação encontrada.</p>
                                @if (!empty($publicationSearch))
                                    <p class="mt-1 text-xs text-gray-500">Tente pesquisar por outro termo.</p>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if ($articles && $articles->hasPages())
                        <div class="border-t border-gray-200 bg-gray-50 px-5 py-3">
                            {{ $articles->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>

    <style>
        .page-content h2 { margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem; font-weight: 700; color: #1f2937; }
        .page-content h3 { margin-top: 1.5rem; margin-bottom: .75rem; font-size: 1.25rem; font-weight: 700; color: #1f2937; }
        .page-content p { margin-bottom: 1rem; line-height: 1.75; }
        .page-content ul { margin-bottom: 1rem; list-style: disc; padding-left: 1.25rem; }
        .page-content ol { margin-bottom: 1rem; list-style: decimal; padding-left: 1.25rem; }
        .page-content a { color: #0369a1; text-decoration: underline; }
        .page-content blockquote { margin: 1rem 0; border-left: 4px solid #e5e7eb; padding: .5rem 0 .5rem 1rem; color: #4b5563; font-style: italic; }
        .page-content strong { font-weight: 600; color: #1f2937; }
    </style>
@endsection
