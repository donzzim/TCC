@php
    $arquivo = 'images/folder.png';
    if (isset($curso) && $curso->arquivos->first()) {
        $arquivo = asset("storage/{$curso->arquivos->first()->arquivo}");
    } else {
    }
@endphp
<x-layouts.portal>
    <x-layouts.portal-header></x-layouts.portal-header>
    <div class="flex flex-col justify-between mt-24 mb-16 h-screem">
        <section>
            <div class="main-div">
                <div class="max-w-md mx-auto overflow-hidden bg-white shadow-md rounded-xl md:max-w-screen-2xl">
                    <div class="md:flex">
                        <div class="md:shrink-0">
                            <img class="object-fill w-[500px] h-[600px]" src="{{ $arquivo }}" />
                        </div>
                        <div class="p-8">
                            <div class="text-2xl font-semibold tracking-wide text-indigo-500 uppercase">
                                {{ $curso ?? 'Sem Titulo' }}</div>

                            <div class="block mt-1 text-xl font-medium leading-tight text-black">
                                @if (count($curso->instrutores))
                                    <div class="my-5">Instrutor(es):
                                        @foreach ($curso->instrutores as $instrutor)
                                            {{ $instrutor->name }}
                                        @endforeach
                                    </div>
                                @endif

                                @if (count($curso->publico))
                                    <div class="my-5">Público alvo(es):
                                        @foreach ($curso->publico as $publico)
                                            {{ $publico->descricao }}
                                        @endforeach
                                    </div>
                                @endif

                                @if (count($curso->atividades))
                                    <div class="flex flex-col my-5">Atividade(s):
                                        @foreach ($curso->atividades as $atividade)
                                            <div class="text-sm text-slate-500">
                                                {{ $atividade->inicio->format('d/m/Y H:i') }}
                                                @if ($atividade->termino)
                                                    a {{ $atividade->termino->format('d/m/Y H:i') }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if ($cancelar_inscricao)
                                <div class="p-4 mb-4 text-lg text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
                                    role="alert">
                                    <span class="font-medium">Confirmado!</span> {{ $cancelar_inscricao }}
                                </div>
                            @endif

                            <p class="my-10 text-gray-500">{!! $curso->descricao !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <x-layouts.portal-footer></x-layouts.portal-footer>
</x-layouts.portal>
