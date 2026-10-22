<section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
    <div class="absolute inset-0 bg-black/20"></div>
    <div class="container relative px-4 py-20 mx-auto sm:px-6 lg:px-8 lg:py-28">
        <div class="max-w-4xl">
            @if (isset($hero->subtitle))
            <span
                class="inline-block px-3 py-1 mb-6 text-sm text-white border rounded-full bg-white/20 border-white/30">
                {{ $hero->subtitle }}
            </span>
            @endif
            @if (isset($hero->title))
            <h1 class="mb-6 text-4xl font-bold leading-tight lg:text-6xl">
                {{ $hero->title }}
            </h1>
            @endif
            <p class="mb-8 text-xl leading-relaxed text-blue-100 lg:text-2xl">
                {{ $hero->text }}
            </p>
            @if (!empty($hero->buttons))
            <div class="flex flex-col gap-4 sm:flex-row">
                @foreach ($hero->buttons as $button)
                @if (isset($button['link']))
                <a
                    href="{{ $button['link'] }}"
                    target="_blank"
                    class="flex items-center justify-center px-6 py-3 font-medium transition-colors rounded-md {{ $button['class'] }}">
                    <i data-lucide="graduation-cap" class="w-5 h-5 mr-2"></i>
                    {{ $button['label'] }}
                </a>
                @else
                <a
                    class="flex items-center justify-center px-6 py-3 font-medium transition-colors rounded-md {{ $button['class'] }}">
                    <i data-lucide="graduation-cap" class="w-5 h-5 mr-2"></i>
                    {{ $button['label'] }}
                </a>
                @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>
