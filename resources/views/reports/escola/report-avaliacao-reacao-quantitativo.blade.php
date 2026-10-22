@extends('reports.layout')

@section('title', 'Relatório de Avaliação de Reação - Quantitativo / Anônimo')
@section('report_name', 'Avaliação de Reação - Quantitativo / Anônimo')

@section('content')
    @forelse($data as $item)
        <table>
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Atividade</th>
                    <th class="text-center">Avaliações respondidas</th>
                    <th class="text-center">Média geral</th>
                    <th class="text-center">Avaliações boas</th>
                    <th class="text-center">Avaliações ruins</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $item['curso']->titulo }}</td>
                    <td>{{ $item['atividade']?->titulo ?? 'Curso completo' }}</td>
                    <td class="text-center">{{ $item['total_avaliacoes'] }}</td>
                    <td class="text-center">{{ $item['media_geral'] }}</td>
                    <td class="text-center">{{ $item['boas'] }}</td>
                    <td class="text-center">{{ $item['ruins'] }}</td>
                </tr>
                <tr>
                    <td colspan="6">
                        <strong>Respostas boas:</strong> {{ $item['respostas_boas'] }}
                        &nbsp;|&nbsp;
                        <strong>Respostas ruins:</strong> {{ $item['respostas_ruins'] }}
                    </td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Aspecto avaliado</th>
                    <th class="text-center">Total de respostas</th>
                    <th class="text-center">Média</th>
                    <th class="text-center">Boas</th>
                    <th class="text-center">Ruins</th>
                    <th>Distribuição das respostas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($item['aspectos'] as $aspecto)
                    <tr>
                        <td>{{ $aspecto['label'] }}</td>
                        <td class="text-center">{{ $aspecto['total_respostas'] }}</td>
                        <td class="text-center">{{ $aspecto['media'] }}</td>
                        <td class="text-center">{{ $aspecto['boas'] }}</td>
                        <td class="text-center">{{ $aspecto['ruins'] }}</td>
                        <td>
                            @foreach($aspecto['distribuicao'] as $resposta => $quantidade)
                                <div>{{ $resposta }}: {{ $quantidade }}</div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Nenhuma resposta registrada para este curso/atividade.</td>
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
                    <td>Nenhuma avaliação de reação encontrada para os filtros informados.</td>
                </tr>
            </tbody>
        </table>
    @endforelse
@endsection
