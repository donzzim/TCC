@extends('reports.layout')

@section('title', 'Relatório de Cursos por Alunos Aprovados, Cargos e Setor - Simplificado')
@section('report_name', 'Cursos por Alunos Aprovados, Cargos e Setor - Simplificado')

@section('content')
    @include('reports.escola.partials.report-cursos-alunos', [
        'data' => $data,
        'tituloAlunos' => 'Alunos Aprovados',
        'mostrarAtividades' => false,
    ])
@endsection
