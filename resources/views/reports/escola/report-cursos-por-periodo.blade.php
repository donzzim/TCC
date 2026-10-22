@extends('reports.layout')

@section('title', 'Relatório de Cursos Ativos')
@section('report_name', 'Cursos por Período')

@section('content')
<table>
    <thead class="text-md">
        <tr>
            <th rowspan="2" class="text-center">ID</th>
            <th rowspan="2">Título</th>
            <th rowspan="2" class="text-center">Carga Horária</th>
            <th rowspan="2" class="text-center">Início</th>
            <th colspan="4" class="text-center">Alunos</th>
            <th colspan="3" class="text-center">Frequência</th>
        </tr>
        <tr>
            <th class="text-center">Pré-inscritos</th>
            <th class="text-center">Inscritos</th>
            <th class="text-center">Indeferido</th>
            <th class="text-center">Cancelado</th>
            <th class="text-center">Aprovados</th>
            <th class="text-center">Ausentes</th>
            <th class="text-center">Presentes</th>
        </tr>
    </thead>
    <tbody class="text-md">
        @forelse($data as $curso)
        <tr>
            <td>{{ $curso->id }}</td>
            <td>{{ $curso->titulo }}</td>
            <td class="text-center">{{ $curso->carga_horaria }} h</td>
            <td>{{ \Carbon\Carbon::parse($curso->data_inicio)->format('d/m/Y') }}</td>
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
            </td>
        </tr>

        @foreach($curso->atividades as $atividade)
        <tr>
            <td>&nbsp;</td>
            <td>{{ $atividade->titulo }}</td>
            <td class="text-center">{{ $atividade->carga_horaria }} h</td>
            <td>{{ \Carbon\Carbon::parse($atividade->data_inicio)->format('d/m/Y') }}</td>
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
        @endforeach
        @empty
        <tr>
            <td colspan="11">Nenhum registro encontrado.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
