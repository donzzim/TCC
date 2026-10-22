<section class="py-16 bg-slate-50">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
            @foreach($stats->stats as $stat)
            <div class="text-center">
                <div class="mb-2 text-3xl font-bold text-blue-900 lg:text-4xl counter"
                    data-target="{{ $stat['value'] }}">
                    0</div>
                <div class="text-slate-600">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>