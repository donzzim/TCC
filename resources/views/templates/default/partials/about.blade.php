<section id="sobre" class="py-20">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">Sobre a
                    EMES</span>
                <h2 class="mb-6 text-3xl font-bold text-slate-900 lg:text-4xl">{{ $about->title }}</h2>
                <p class="mb-6 text-lg leading-relaxed text-slate-600">{{ $about->text1 }}</p>
                <p class="mb-8 text-lg leading-relaxed text-slate-600">{{ $about->text2 }}</p>
            </div>
            <div class="relative">
                <div class="aspect-[4/3] bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl">
                    <img src="{{ Storage::url($about->image) }}" class="rounded-lg pt-6">
                </div>
            </div>
        </div>
    </div>
</section>
