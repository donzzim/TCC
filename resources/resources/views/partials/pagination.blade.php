@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
    {{-- Legenda de Resultados (para telas maiores) --}}
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-start">
        <p class="text-sm text-gray-700 leading-5">
            Mostrando
            <span class="font-medium">{{ $paginator->firstItem() }}</span>
            a
            <span class="font-medium">{{ $paginator->lastItem() }}</span>
            de
            <span class="font-medium">{{ $paginator->total() }}</span>
            resultados
        </p>
    </div>

    {{-- Container dos Botões e Números --}}
    <div class="flex-1 flex justify-between sm:justify-end">
        {{-- Botão "Anterior" --}}
        <span>
            @if ($paginator->onFirstPage())
            <span
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md">
                {!! __('pagination.previous') !!}
            </span>
            @else
            <a href="{{ $paginator->previousPageUrl() }}"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {!! __('pagination.previous') !!}
            </a>
            @endif
        </span>

        {{-- Links das Páginas (visível apenas em telas maiores) --}}
        <div class="hidden sm:flex sm:items-center sm:space-x-2 mx-4">
            @foreach ($elements as $element)
            {{-- Reticências "..." --}}
            @if (is_string($element))
            <span
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">
                {{ $element }}
            </span>
            @endif

            {{-- Array de Links de Página --}}
            @if (is_array($element))
            @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
            <span aria-current="page"
                class="relative z-10 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-800 border border-blue-800 rounded-md cursor-default">
                {{ $page }}
            </span>
            @else
            <a href="{{ $url }}"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {{ $page }}
            </a>
            @endif
            @endforeach
            @endif
            @endforeach
        </div>

        {{-- Botão "Próximo" --}}
        <span>
            @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {!! __('pagination.next') !!}
            </a>
            @else
            <span
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md">
                {!! __('pagination.next') !!}
            </span>
            @endif
        </span>
    </div>
</nav>
@endif