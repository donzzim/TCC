@extends('templates.default.layouts.app')

@section('content')
    @if(!empty($hero->title))
        @include('templates.default.partials.hero', ['hero' => $hero])
    @else
        <section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="container relative px-4 py-10 mx-auto sm:px-6 lg:px-8 lg:py-14">
                <div class="max-w-4xl">
                    <span class="inline-block px-3 py-1 mb-6 text-sm text-white border rounded-full bg-white/20 border-white/30">
                        EMES - Escola da Magistratura do Estado do Espírito Santo
                    </span>
                    <h1 class="mb-6 text-4xl font-bold leading-tight lg:text-6xl">Ações Disponíveis</h1>
                    <p class="mb-4 text-xl leading-relaxed text-blue-100 lg:text-2xl">Ações Disponíveis</p>
                </div>
            </div>
        </section>
    @endif

    @include('templates.default.partials.course-browser', [
        'heading' => 'Ações Disponíveis',
        'sectionClasses' => 'py-16 bg-slate-50',
        'emptyMessage' => 'Nenhuma ação encontrada no momento.',
        'cardsLimit' => 12,
        'cardsPageSize' => 12,
        'cardsLazyLoad' => true,
        'listInitialLimit' => 15,
        'listPageSize' => 15,
    ])
@endsection
