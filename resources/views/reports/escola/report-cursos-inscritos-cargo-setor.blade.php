@extends('reports.layout')

@section('title', 'Relatório de Cursos por Alunos Inscritos, Cargos e Setor')
@section('report_name', 'Cursos por Alunos Inscritos, Cargos e Setor')

@section('content')
    @include('reports.escola.partials.report-cursos-alunos', [
        'data' => $data,
        'tituloAlunos' => 'Alunos Inscritos',
        'mostrarAtividades' => true,
    ])
@endsection
