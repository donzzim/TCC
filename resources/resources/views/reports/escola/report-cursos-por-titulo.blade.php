@extends('reports.layout')

@section('title', 'Relatório de Cursos Ativos')
@section('report_name', 'Cursos pelo Título')

@section('content')
<table>
    <thead class="text-center text-lg bg-stone-800">
        <tr>
            <th rowspan="2" class="text-center">ID</th>
            <th rowspan="2" class="text-lg bg-stone-800">Título</th>
            <th rowspan="2" class="text-center">Carga Horária</th>
            <th rowspan="2" class="text-center">Período</th>
            <th colspan="4" class="text-center">Alunos</th>
            <th colspan="3" class="text-center">Frequência</th>
        </tr>
        <tr>
            <th class="text-center">Pré-inscritos</th>
            <th class="text-center">Inscritos</th>
            <th class="text-center">Indeferidos</th>
            <th class="text-center">Cancelados</th>
            <th class="text-center">Aprovados</th>
            <th class="text-center">Ausentes</th>
            <th class="text-center">Presentes</th>
        </tr>
    </thead>
    <tbody class="text-lg">
        @forelse($data as $curso)
        <tr>
            <td>{{ $curso->id }}</td>
            <td>{{ $curso->titulo }}</td>
            <td class="text-center">{{ $curso->carga_horaria }} h</td>
            <td class="text-center">
                {{ \Carbon\Carbon::parse($curso->data_inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($curso->data_termino)->format('d/m/Y') }}
            </td>
            <td class="text-center">{{ $curso->alunos->where('situacaoinscricao_id',
                config('settings.inscricao.preinscricao'))->count() }}</td>
            <td class="text-center">{{
                $curso->alunos->where('situacaoinscricao_id',config('settings.inscricao.inscrito'))->count() }}</td>
            <td class="text-center">{{
                $curso->alunos->where('situacaoinscricao_id',config('settings.inscricao.indeferido'))->count() }}</td>
            <td class="text-center">{{
                $curso->alunos->where('situacaoinscricao_id',config('settings.inscricao.cancelado'))->count() }}</td>
            <td class="text-center">{{ $curso->alunos->where('aprovado', true)->count() }}</td>
            <td class="text-center">{{
                $curso->frequencias->where('situacaofrequencia_id',config('settings.frequencia.ausente'))->count() }}
            </td>
            <td class="text-center">{{
                $curso->frequencias->where('situacaofrequencia_id',config('settings.frequencia.presente'))->count() }}
            </td>
        </tr>

        <tr>
            <td colspan="11">
                <table>
                    <thead class="text-sm">
                        <tr>
                            <th colspan="7" class="text-left bg-stone-400 text-stone-800">Alunos</th>
                        </tr>
                        <tr>
                            <th class="text-center bg-stone-400 text-stone-800">CPF</th>
                            <th class="text-left bg-stone-400 text-stone-800">Nome</th>
                            <th class="text-left bg-stone-400 text-stone-800">Email</th>
                            <th class="text-left bg-stone-400 text-stone-800">Setor</th>
                            <th class="text-center bg-stone-400 text-stone-800">Inscrição</th>
                            <th class="text-center bg-stone-400 text-stone-800">Aprovação</th>
                            <th class="text-center bg-stone-400 text-stone-800 w-52">Frequências</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($curso->alunos as $inscricao)
                        <tr>
                            <td class="text-center">{{ $inscricao->aluno->cpf ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->name ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->email ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->setorPrincipal[0]['setor'] ?? 'N/D' }}</td>
                            <td class="text-center">{{ $inscricao->situacaoinscricao->descricao ?? 'N/D' }}</td>
                            <td class="text-center">
                                @if($inscricao->aprovado) 
                                    <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">Aprovado</div>
                                @else
                                    <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200 hover:bg-red-100 cursor-pointer transition">Reprovado</div>
                                @endif
                            </td>
                            <td class="text-center">
                                    @foreach($inscricao->frequencias as $frequencia)
                                        @if($frequencia->situacaofrequencia_id == config('settings.frequencia.presente'))
                                        <div
                                            class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">
                                            {{ \Carbon\Carbon::parse($frequencia->data->inicio)->format('d/m/Y') }}
                                        </div>

                                        @elseif($frequencia->situacaofrequencia_id == config('settings.frequencia.ausente'))
                                        {{-- Badge Vermelho vira Botão --}}
                                        <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200 hover:bg-red-100">
                                            {{ \Carbon\Carbon::parse($frequencia->data->inicio)->format('d/m/Y') }}
                                        </div>
                                        @else
                                        <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 bg-yellow-500">
                                            {{ \Carbon\Carbon::parse($frequencia->data->inicio)->format('d/m/Y') }}
                                        </div>
                                        @endif
                                    @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>

        @foreach($curso->atividades as $atividade)
        <tr>
            <td>&nbsp;</td>
            <td>{{ $atividade->titulo }}</td>
            <td class="text-center">{{ $atividade->carga_horaria }} h</td>
            <td>
                {{ \Carbon\Carbon::parse($curso->data_inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($curso->data_termino)->format('d/m/Y') }}
            </td>
            <td class="text-center">{{ $atividade->alunos->where('situacaoinscricao_id',
                config('settings.inscricao.preinscricao'))->count() }}</td>
            <td class="text-center">{{
                $atividade->alunos->where('situacaoinscricao_id',config('settings.inscricao.inscrito'))->count() }}</td>
            <td class="text-center">{{
                $atividade->alunos->where('situacaoinscricao_id',config('settings.inscricao.indeferido'))->count() }}
            </td>
            <td class="text-center">{{
                $atividade->alunos->where('situacaoinscricao_id',config('settings.inscricao.cancelado'))->count() }}
            </td>
            <td class="text-center">{{ $atividade->alunos->where('aprovado', true)->count() }}</td>
            <td class="text-center">{{
                $atividade->frequencias->where('situacaofrequencia_id',config('settings.frequencia.ausente'))->count()
                }}
            </td>
            <td class="text-center">{{
                $atividade->frequencias->where('situacaofrequencia_id',config('settings.frequencia.presente'))->count()
                }}
            </td>
            </td>
        </tr>

        <tr>
            <td colspan="11">
                <table>
                    <thead class="text-sm">
                        <tr>
                            <th colspan="6" class="text-left bg-stone-400 text-stone-800">Alunos</th>
                        </tr>
                        <tr>
                            <th class="text-center bg-stone-400 text-stone-800">CPF</th>
                            <th class="text-left bg-stone-400 text-stone-800">Nome</th>
                            <th class="text-left bg-stone-400 text-stone-800">Email</th>
                            <th class="text-left bg-stone-400 text-stone-800">Setor</th>
                            <th class="text-center bg-stone-400 text-stone-800">Inscrição</th>
                            <th class="text-center bg-stone-400 text-stone-800">Aprovação</th>
                            <th class="text-center bg-stone-400 text-stone-800 w-52">Frequências</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($atividade->alunos as $inscricao)
                        <tr>
                            <td>{{ $inscricao->aluno->cpf ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->name ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->email ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->setorPrincipal[0]['setor'] ?? 'N/D' }}</td>
                            <td>{{ $inscricao->situacaoinscricao->descricao ?? 'N/D' }}</td>
                            <td class="text-center">
                                @if($inscricao->aprovado) 
                                    <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">Aprovado</div>
                                @else
                                    <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200 hover:bg-red-100 cursor-pointer transition">Reprovado</div>
                                @endif
                            </td>
                            <td class="text-center">
                                    @foreach($inscricao->frequencias as $frequencia)
                                        @if($frequencia->situacaofrequencia_id == config('settings.frequencia.presente'))
                                        <div
                                            class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">
                                            {{ \Carbon\Carbon::parse($frequencia->data->inicio)->format('d/m/Y') }}
                                        </div>

                                        @elseif($frequencia->situacaofrequencia_id == config('settings.frequencia.ausente'))
                                        {{-- Badge Vermelho vira Botão --}}
                                        <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200 hover:bg-red-100">
                                            {{ \Carbon\Carbon::parse($frequencia->data->inicio)->format('d/m/Y') }}
                                        </div>
                                        @else
                                        <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 bg-yellow-500">
                                            {{ \Carbon\Carbon::parse($frequencia->data->inicio)->format('d/m/Y') }}
                                        </div>
                                        @endif
                                    @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>

        @endforeach
        @empty
        <tr>
            <td colspan="11">Nenhum registro encontrado.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
