@extends('templates.default.layouts.app')

@section('content')

    {{-- Seção do cabeçalho da página --}}
    <section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="container relative px-4 py-10 mx-auto sm:px-6 lg:px-8 lg:py-14">
            <div class="max-w-4xl">
                <h1 class="mb-4 text-4xl font-bold leading-tight lg:text-5xl">
                    Consulta de Certificados
                </h1>
                <p class="text-xl leading-relaxed text-blue-100 lg:text-2xl">
                    Informe seu CPF para localizar e visualizar seus certificados de conclusão.
                </p>
            </div>
        </div>
    </section>

    {{-- Seção de busca e resultados --}}
    <div class="bg-slate-50">
        <div class="container mx-auto px-4 py-16 lg:px-8">

            {{-- Formulário de Busca por CPF --}}
            <div class="max-w-xl p-6 mx-auto mb-12 bg-white rounded-lg shadow-md">
                <form action="{{ route('portal.certificados') }}" method="GET">
                    @csrf
                    <label for="cpf" class="block text-lg font-semibold text-gray-800">Digite seu CPF</label>
                    <p class="mb-4 text-sm text-gray-600">Apenas números serão considerados.</p>
                    <div class="flex items-center gap-4">
                        <input type="text" name="cpf" id="cpf" value="{{ $cpf_buscado ?? '' }}"
                            placeholder="000.000.000-00"
                            class="block w-full px-4 py-2 text-lg border border-slate-200 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <button type="submit"
                            class="px-6 py-2 font-semibold text-white bg-blue-800 rounded-md shadow-sm hover:bg-blue-900 transition-colors h-full">
                            <i data-lucide="search" class="inline w-5 h-5"></i>
                            <span class="hidden sm:inline ml-2">Buscar</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Seção de Resultados --}}
            @if ($cpf_buscado)
                <header class="mb-8 text-center">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                        Exibindo {{ $certificados->total() }} {{ Str::plural('certificado', $certificados->total()) }}
                    </h2>
                </header>

                @if ($certificados->isNotEmpty())
                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($certificados as $certificado)
                            <div
                                class="flex flex-col overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-xl">
                                <div class="flex flex-col flex-1 p-6">
                                    @php
                                        if ($certificado->certificable->curso_id) {
                                            $curso = $certificado->certificable->curso;
                                        } else {
                                            $curso = $certificado->certificable;
                                        }
                                    @endphp

                                    <span
                                        class="mb-2 text-xs font-semibold tracking-wide text-blue-700 uppercase">{{ $curso->titulo ?? 'Curso não informado' }}</span>
                                    <h3 class="flex-1 mb-4 text-lg font-semibold text-slate-900">
                                        {{ $certificado->titulo ?? $certificado->certificable->titulo }}</h3>
                                    <p class="mb-4 text-sm text-slate-500">Emitido para:
                                        <strong>{{ $certificado->nome }}</strong></p>
                                    <a href="{{ route('imprimir.certificado', $certificado->uuid) }}" target="_blank"
                                        class="w-full px-4 py-2 mt-auto font-semibold text-center text-white bg-blue-800 rounded-md hover:bg-blue-900 transition-colors">
                                        Visualizar Certificado
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Paginação --}}
                    <div class="mt-12">
                        {{ $certificados->links('partials.pagination') }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <i data-lucide="shield-x" class="w-16 h-16 mx-auto text-gray-400"></i>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">Nenhum certificado encontrado</h3>
                        <p class="mt-1 text-sm text-gray-500">Não foram encontrados certificados para o CPF informado.
                            Verifique o
                            número e tente novamente.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            lucide.createIcons();
        });
    </script>
@endsection
