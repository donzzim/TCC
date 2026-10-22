@extends('templates.default.layouts.app')

@section('content')

@php
// Lógica para buscar a imagem principal do curso
$headerImage = $curso->arquivos->first() ? asset("storage/{$curso->arquivos->first()->arquivo}") : null;
@endphp

{{-- Estilos para o conteúdo rico (como em post.blade.php) --}}
<style>
    .course-content h2 {
        @apply text-2xl font-bold mt-8 mb-4 text-gray-800;
    }

    .course-content h3 {
        @apply text-xl font-bold mt-6 mb-3 text-gray-800;
    }

    .course-content p {
        @apply mb-4 leading-relaxed text-gray-600;
    }

    .course-content ul {
        @apply list-disc list-inside mb-4 pl-4 space-y-2;
    }

    .course-content a {
        @apply text-blue-700 underline hover: text-blue-900 transition-colors;
    }
</style>

<section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
    <div class="absolute inset-0 bg-black/20"></div>
    <div class="container relative px-4 py-10 mx-auto sm:px-6 lg:px-8 lg:py-14">
        <div class="flex flex-col items-center gap-8 md:flex-row md:gap-12">
            <div class="flex-1 max-w-4xl text-center md:text-left">
                <span
                    class="inline-block px-3 py-1 mb-6 text-sm text-white border rounded-full bg-white/20 border-white/30">
                    EMES - Escola da Magistratura do Estado do Espírito Santo
                </span>
                <h1 class="mb-6 text-4xl font-bold leading-tight lg:text-5xl">
                    {{ $curso->titulo ?? 'Sem Titulo' }}
                </h1>
                <p class="text-xl leading-relaxed text-blue-100 lg:text-2xl">
                    {!! Str::limit($curso->descricao, 150) !!}
                </p>
            </div>

            @if($headerImage)
            <div class="md:w-1/3 lg:w-1/4">
                <figure class="flex justify-center bg-gray-50 rounded-lg">
                    <img class="w-auto h-auto max-h-[400px] rounded-lg shadow-lg border-blue-800 border"
                        src="{{ $headerImage }}"
                        alt="{{ $curso->titulo }}">
                </figure>
            </div>
            @endif

        </div>
    </div>
</section>

<div class="bg-white">
    <div class="container mx-auto px-4 py-16 lg:px-8">
        <div class="mx-auto max-w-8xl">
            {{-- Notificação de Ação (ex: cancelamento) --}}
            @if (!empty($cancelar_inscricao))
            <div class="p-4 mb-6 text-green-800 bg-green-100 border-l-4 border-green-500 rounded-md" role="alert">
                <p class="font-bold">Sucesso!</p>
                <p>{{ $cancelar_inscricao }}</p>
            </div>
            @endif

            {{-- Botões de Ação --}}
            <div class="flex flex-wrap items-center gap-4 p-6 mb-8 border border-slate-300 rounded-lg bg-slate-50">
                <h3 class="w-full text-lg font-semibold text-gray-800 md:w-auto">Ações do Curso:</h3>
                <div class="flex flex-wrap items-center gap-4">
                    @if($curso->modalidade_id == config('settings.modalidade.ead'))
                        <a href="{{ $curso->link }}" target="_blank" class="px-5
                            py-2 font-semibold text-white bg-green-600 rounded-md shadow-sm hover:bg-green-700
                            transition-colors">
                            <i data-lucide="globe" class="inline w-4 h-4 mr-2"></i> Acessar o Curso do EaD
                        </a>
                    @endif

                    @if($curso->liberar_inscricao)
                    <a href="{{ route('realizar.inscricao', ['uuid'=> $curso->uuid ]) }}" target="_blank" class="px-5
                        py-2 font-semibold text-white bg-green-600 rounded-md shadow-sm hover:bg-green-700
                        transition-colors">
                        <i data-lucide="edit-3" class="inline w-4 h-4 mr-2"></i> Inscrever-se
                    </a>
                    @endif

                    {{-- Supondo que a lógica para mostrar este botão venha do Controller --}}
                    @if(!empty($pode_cancelar))
                    <a href="{{ route('cancelar.inscricao', ['uuid'=> $curso->uuid ]) }}" class="px-5 py-2
                        font-semibold text-white bg-red-600 rounded-md shadow-sm hover:bg-red-700 transition-colors">
                        <i data-lucide="x-circle" class="inline w-4 h-4 mr-2"></i> Cancelar Inscrição
                    </a>
                    @endif

                    @if($curso->liberar_frequencia)
                    <a href="{{ route('realizar.frequencia', ['uuid'=> $curso->uuid ]) }}" target="_blank" class="px-5
                        py-2 font-semibold text-white bg-yellow-500 rounded-md shadow-sm hover:bg-yellow-600
                        transition-colors">
                        <i data-lucide="check-square" class="inline w-4 h-4 mr-2"></i> Registrar Frequência
                    </a>
                    @endif
                </div>
            </div>

            {{-- Grid Principal: Conteúdo + Sidebar de Informações --}}
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">

                {{-- Coluna Principal de Conteúdo --}}
                <div class="lg:col-span-2 course-content">
                    @if($curso->objetivo_geral)
                    <section class="mb-8">
                        <h2 class="border-b pb-2">Objetivo Geral</h2>
                        <div class="mt-4">{!! $curso->objetivo_geral !!}</div>
                    </section>
                    @endif

                    @if($curso->conteudo_programatico)
                    <section class="mb-8">
                        <h2 class="border-b pb-2">Conteúdo Programático</h2>
                        <div class="mt-4">{!! $curso->conteudo_programatico !!}</div>
                    </section>
                    @endif

                    @if(!$curso->objetivo_geral && !$curso->conteudo_programatico && !$curso->justificativa)
                    <section class="mb-8">
                        <h2 class="border-b pb-2">Descrição</h2>
                        <div class="mt-4">{!! $curso->descricao !!}</div>
                    </section>
                    @endif
                </div>

                {{-- Sidebar de Metadados --}}
                <aside class="p-6 border  border-slate-300 rounded-lg bg-slate-50 lg:sticky lg:top-24 h-fit">
                    <h3 class="text-xl font-semibold text-gray-900 border-b  border-slate-300 pb-3 mb-4">Detalhes do
                        Curso</h3>
                    <ul class="space-y-4 text-gray-600">
                        @if ($curso->atividades->count())
                        <li class="flex items-start">
                            <i data-lucide="calendar" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div>
                                <strong>Data:</strong>
                                @foreach ($curso->atividades as $atividade)
                                <br>
                                    @foreach($atividade->agendas as $agenda)
                                    <strong>{{ $atividade->titulo }}</strong> <span>{{ $agenda->inicio->format('d/m/Y
                                        H:i') }} a
                                        {{ $agenda->termino->format('d/m/Y H:i') }}</span>
                                        <br>
                                    @endforeach
                                @endforeach
                            </div>
                        </li>
                        @else
                            @foreach($curso->agendas as $agenda)
                                @if($agenda->inicio->format('d/m/Y') == $agenda->termino->format('d/m/Y'))
                                <li class="flex items-start">
                                    <i data-lucide="calendar" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                                    <div>
                                        <strong>Data:</strong>
                                        {{ $agenda->inicio->format('d/m/Y H:i') }} a {{ $agenda->termino->format('H:i') }}
                                    </div>
                                </li>
                                @else
                                <li class="flex items-start">
                                    <i data-lucide="calendar" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                                    <div>
                                        <strong>Data:</strong>
                                        {{ $agenda->inicio->format('d/m/Y H:i') }} a {{ $agenda->termino->format('d/m/Y H:i') }}
                                    </div>
                                </li>
                                @endif
                            @endforeach
                        @endif
                        @if ($curso->carga_horaria)
                        <li class="flex items-start">
                            <i data-lucide="clock" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div><strong>Carga Horária:</strong> {{ $curso->carga_horaria }} horas</div>
                        </li>
                        @endif
                        @if($curso->numero_vagas)
                        <li class="flex items-start">
                            <i data-lucide="users" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div><strong>Vagas:</strong> {{ $curso->numero_vagas }}</div>
                        </li>
                        @endif
                        <li class="flex items-start">
                            <i data-lucide="award" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div><strong>Certificável:</strong> {{ $curso->certificavel ? 'Sim' : 'Não' }}</div>
                        </li>
                        @if($curso->modalidade)
                        <li class="flex items-start">
                            <i data-lucide="laptop" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div><strong>Modalidade:</strong> {{ $curso->modalidade->descricao }}</div>
                        </li>
                        @endif
                        @if($curso->instrutores->isNotEmpty())
                        <li class="flex items-start">
                            <i data-lucide="graduation-cap" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div><strong>Instrutor(es):</strong>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($curso->instrutores as $instrutor)
                                    @php
                                    $user = \app\Models\User::find($instrutor->instrutor_id);
                                    @endphp
                                    @if (isset($user->name))
                                    <span class="px-2 py-0.5 text-xs bg-slate-200 text-slate-800 rounded-full">{{
                                        $user->name }}</span>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </li>
                        @endif
                        @if($curso->publico->isNotEmpty())
                        <li class="flex items-start">
                            <i data-lucide="target" class="w-5 h-5 mr-3 mt-1 text-blue-800 shrink-0"></i>
                            <div><strong>Público-Alvo:</strong>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($curso->publico as $publico)
                                    <span class="px-2 py-0.5 text-xs bg-slate-200 text-slate-800 rounded-full">{{
                                        $publico->descricao }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                        @endif
                    </ul>
                </aside>
            </div>
        </div>
    </div>
</div>
@endsection
