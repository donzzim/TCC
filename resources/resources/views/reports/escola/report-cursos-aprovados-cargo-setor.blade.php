@extends('reports.layout')

@section('title', 'Relatório de Cursos por Alunos Aprovados, Cargos e Setor')
@section('report_name', 'Cursos por Alunos Aprovados, Cargos e Setor')

@section('content')
<table>
    <thead class="text-center text-lg bg-stone-800">
        <tr>
            <th class="text-center">ID</th>
            <th class="text-lg bg-stone-800">Título</th>
            <th class="text-center">Carga Horária</th>
            <th class="text-center">Período</th>
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
                            <th class="text-left bg-stone-400 text-stone-800">Cargo</th>
                            <th class="text-left bg-stone-400 text-stone-800">Setor</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($curso->alunos as $inscricao)
                        <tr>
                            <td class="text-center">{{ $inscricao->aluno->cpf ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->name ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->email ?? 'N/D' }}</td>
                            <td>
                                @if ($inscricao->aluno->recadastro)
                                    @if ($inscricao->aluno->recadastro->cargo)
                                    {{ $inscricao->aluno->recadastro?->cargo }}
                                    @elseif ($inscricao->aluno->recadastro->funcao)
                                    {{ $inscricao->aluno->recadastro?->funcao }}
                                    @elseif ($inscricao->aluno->recadastro->tipovinc)
                                    {{ $inscricao->aluno->recadastro?->tipovinc }}
                                    @else
                                    Público Externo
                                    @endif
                                @else
                                    Público Externo
                                @endif
                            </td>
                            <td>
                                @if ($inscricao->aluno->recadastro)
                                    @if ($inscricao->aluno->recadastro->nomesetor)
                                    {{ $inscricao->aluno->recadastro?->nomesetor }}
                                    @else
                                    EMES - Escola da Magistratura do Estado do Espírito Santo
                                    @endif
                                @else
                                    EMES - Escola da Magistratura do Estado do Espírito Santo
                                @endif
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
                            <th class="text-center bg-stone-400 text-stone-800">Cargo</th>
                            <th class="text-left bg-stone-400 text-stone-800">Setor</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($atividade->alunos as $inscricao)
                        <tr>
                            <td>{{ $inscricao->aluno->cpf ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->name ?? 'N/D' }}</td>
                            <td>{{ $inscricao->aluno->email ?? 'N/D' }}</td>
                            <td>
                                @if ($inscricao->aluno->recadastro)
                                    @if ($inscricao->aluno->recadastro->cargo)
                                    {{ $inscricao->aluno->recadastro?->cargo }}
                                    @elseif ($inscricao->aluno->recadastro->funcao)
                                    {{ $inscricao->aluno->recadastro?->funcao }}
                                    @elseif ($inscricao->aluno->recadastro->tipovinc)
                                    {{ $inscricao->aluno->recadastro?->tipovinc }}
                                    @else
                                    Público Externo
                                    @endif
                                @else
                                    Público Externo
                                @endif
                            </td>
                            <td>
                                @if ($inscricao->aluno->recadastro)
                                    @if ($inscricao->aluno->recadastro->nomesetor)
                                    {{ $inscricao->aluno->recadastro?->nomesetor }}
                                    @else
                                    EMES - Escola da Magistratura do Estado do Espírito Santo
                                    @endif
                                @else
                                    EMES - Escola da Magistratura do Estado do Espírito Santo
                                @endif
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
