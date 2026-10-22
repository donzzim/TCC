<!-- News Section -->
<section id="noticias" class="py-20">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
            <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">
                Cursos
            </span>
            <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl">Cursos Disponíveis</h2>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">

            @foreach ($cursos as $curso)
            @php
            $arquivo = 'images/folder.png';
            $descricao = $curso->descricao;
            if(!$curso->externo){
            $descricao = "";
            if (count($curso->instrutores)){
            $descricao .= "<div>Instrutor(es):";
                foreach ($curso->instrutores as $instrutor){
                $descricao .= '<span
                    class="mx-2 bg-slate-100 text-slate-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-slate-700 dark:text-slate-300">'.
                    $instrutor->name . '</span>';
                }
                $descricao .= "</div>";
            }

            if (count($curso->publico)){
            $descricao .= "<span>Público alvo(es):";
                foreach ($curso->publico as $publico){
                $descricao .= '<span
                    class="mx-2 bg-slate-100 text-slate-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-slate-700 dark:text-slate-300">'.
                    $publico->descricao . '</span>';
                }
                $descricao .= "</span>";
            }

            if (count($curso->atividades)){
            $descricao .= '<div class="flex flex-col">Atividade(s):';
                foreach ($curso->atividades as $atividade){
                $descricao .= "<div>{$atividade->inicio->format('d/m/Y H:i')}";
                    if($atividade->termino){
                    if($atividade->termino->format('d/m/Y') == $atividade->inicio->format('d/m/Y')){
                    $descricao .= " - {$atividade->termino->format('H:i')}";
                    } else {
                    $descricao .= " a {$atividade->termino->format('d/m/Y H:i')}";
                    }
                    }
                    $descricao .= "</div>";
                }
                $descricao .= "</div>";
            }
            }

            if(isset($curso->arquivos->first()->arquivo)){
            $arquivo = asset("storage/{$curso->arquivos->first()->arquivo}");
            }
            @endphp
            <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                <div class="aspect-video bg-gradient-to-br from-blue-100 to-blue-200">
                    <img class="main-course-card-img" src="{{ $arquivo }}" alt="" />
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-2 space-x-2 text-sm text-slate-500">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>{{ $curso->inicio }}</span>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-slate-900">
                        {{ $curso->titulo }}
                    </h3>
                    <p class="mb-4 text-sm text-slate-600">
                        {!! $descricao !!}
                    </p>
                    <a class="main-btn-blue" href="{{ route(" portal.course", ['uuid'=> $curso->uuid ]) }}">
                        Leia mais →
                    </a>
                    @if($curso->liberar_inscricao)
                    <a href="{{ route(" realizar.inscricao", ['uuid'=> $curso->uuid ]) }}" target="_blank"
                        class="main-btn-gree">
                        Inscrever&nbsp;<i class="fa-solid fa-check"></i>
                    </a>
                    @endif

                    @if($curso->liberar_frequencia)
                    <a href="{{ route(" realizar.frequencia", ['uuid'=> $curso->uuid ]) }}" target="_blank"
                        class="main-btn-yellow">
                        Frequência&nbsp;<i class="fa-solid fa-check"></i>
                    </a>
                    @endif
                </div>
            </div>
            @endforeach

            {{-- <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                <div class="aspect-video bg-gradient-to-br from-green-100 to-green-200"></div>
                <div class="p-6">
                    <div class="flex items-center mb-2 space-x-2 text-sm text-slate-500">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>10 de Janeiro, 2024</span>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-slate-900">
                        Seminário sobre Direito Digital e IA
                    </h3>
                    <p class="mb-4 text-sm text-slate-600">
                        A EMES promoverá seminário sobre os impactos da inteligência artificial no direito...
                    </p>
                    <button class="text-sm font-medium text-blue-900 hover:text-blue-700">
                        Leia mais →
                    </button>
                </div>
            </div>

            <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                <div class="aspect-video bg-gradient-to-br from-purple-100 to-purple-200"></div>
                <div class="p-6">
                    <div class="flex items-center mb-2 space-x-2 text-sm text-slate-500">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>5 de Janeiro, 2024</span>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-slate-900">
                        Parceria com universidades internacionais
                    </h3>
                    <p class="mb-4 text-sm text-slate-600">
                        Nova parceria permitirá intercâmbio de conhecimento com instituições europeias...
                    </p>
                    <button class="text-sm font-medium text-blue-900 hover:text-blue-700">
                        Leia mais →
                    </button>
                </div>
            </div> --}}
        </div>
    </div>
</section>