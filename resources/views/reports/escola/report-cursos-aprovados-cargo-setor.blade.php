@extends('reports.layout')

@section('title', 'Relatório de Cursos por Alunos Aprovados, Cargos e Setor')
@section('report_name', 'Cursos por Alunos Aprovados, Cargos e Setor')

@section('content')
    @include('reports.escola.partials.report-cursos-alunos', [
        'data' => $data,
        'tituloAlunos' => 'Alunos Aprovados',
        'mostrarAtividades' => true,
    ])
@endsection
