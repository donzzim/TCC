@extends('reports.layout')

@section('title', 'Relatório de Avaliação de Reação - Descritivo por Aluno Aprovado')
@section('report_name', 'Avaliação de Reação - Descritivo por Aluno Aprovado')

@section('content')
    @forelse($data as $item)
        <table>
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Atividade</th>
                    <th>Formulário</th>
                    <th class="text-center">Média</th>
                    <th class="text-center">Enviado em</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $item['curso']->titulo }}</td>
                    <td>{{ $item['atividade']?->titulo ?? 'Curso completo' }}</td>
                    <td>{{ $item['submission']->form?->name ?? 'N/D' }}</td>
                    <td class="text-center">{{ $item['media'] }}</td>
                    <td class="text-center">{{ $item['submission']->created_at?->format('d/m/Y H:i') ?? 'N/D' }}</td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead class="text-sm">
                <tr>
                    <th colspan="9" class="text-left bg-stone-400 text-stone-800">Aluno Aprovado</th>
                </tr>
                <tr>
                    <th class="text-center bg-stone-400 text-stone-800">CPF</th>
                    <th class="text-left bg-stone-400 text-stone-800">Nome</th>
                    <th class="text-left bg-stone-400 text-stone-800">Email</th>
                    <th class="text-left bg-stone-400 text-stone-800">Vínculo</th>
                    <th class="text-left bg-stone-400 text-stone-800">Sexo</th>
                    <th class="text-left bg-stone-400 text-stone-800">Raça/Cor</th>
                    <th class="text-left bg-stone-400 text-stone-800">PCD / Deficiência</th>
                    <th class="text-left bg-stone-400 text-stone-800">Cargo</th>
                    <th class="text-left bg-stone-400 text-stone-800">Setor</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @include('reports.escola.partials.aluno-relatorio-row', ['inscricao' => $item['inscricao']])
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Pergunta</th>
                    <th>Resposta</th>
                    <th class="text-center">Nota calculada</th>
                </tr>
            </thead>
            <tbody>
                @forelse($item['respostas'] as $resposta)
                    <tr>
                        <td>{{ $resposta['pergunta'] }}</td>
                        <td>{{ $resposta['resposta'] }}</td>
                        <td class="text-center">{{ $resposta['score'] !== null ? number_format($resposta['score'], 2, ',', '.') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Nenhuma resposta registrada para esta avaliação.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @empty
        <table>
            <tbody>
                <tr>
                    <td>Nenhum aluno aprovado com avaliação de reação respondida foi encontrado para os filtros informados.</td>
                </tr>
            </tbody>
        </table>
    @endforelse
@endsection
