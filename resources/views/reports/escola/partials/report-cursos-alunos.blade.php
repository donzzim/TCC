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
            @php
                $periodoCurso = ($curso->inicio ? \Carbon\Carbon::parse($curso->inicio)->format('d/m/Y') : 'N/D')
                    . ' a '
                    . ($curso->termino ? \Carbon\Carbon::parse($curso->termino)->format('d/m/Y') : 'N/D');
            @endphp

            <tr>
                <td>{{ $curso->id }}</td>
                <td>{{ $curso->titulo }}</td>
                <td class="text-center">{{ $curso->carga_horaria }} h</td>
                <td class="text-center">{{ $periodoCurso }}</td>
            </tr>

            <tr>
                <td colspan="4">
                    <table>
                        <thead class="text-sm">
                            <tr>
                                <th colspan="9" class="text-left bg-stone-400 text-stone-800">{{ $tituloAlunos ?? 'Alunos' }}</th>
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
                            @forelse($curso->alunos as $inscricao)
                                @include('reports.escola.partials.aluno-relatorio-row', ['inscricao' => $inscricao])
                            @empty
                                <tr>
                                    <td colspan="9">Nenhum aluno encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>

            @if($mostrarAtividades ?? true)
                @foreach($curso->atividades as $atividade)
                    @php
                        $periodoAtividade = ($atividade->inicio ? \Carbon\Carbon::parse($atividade->inicio)->format('d/m/Y') : 'N/D')
                            . ' a '
                            . ($atividade->termino ? \Carbon\Carbon::parse($atividade->termino)->format('d/m/Y') : 'N/D');
                    @endphp

                    <tr>
                        <td>&nbsp;</td>
                        <td>{{ $atividade->titulo }}</td>
                        <td class="text-center">{{ $atividade->carga_horaria }} h</td>
                        <td class="text-center">{{ $periodoAtividade }}</td>
                    </tr>

                    <tr>
                        <td colspan="4">
                            <table>
                                <thead class="text-sm">
                                    <tr>
                                        <th colspan="9" class="text-left bg-stone-400 text-stone-800">{{ $tituloAlunos ?? 'Alunos' }} da Atividade</th>
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
                                    @forelse($atividade->alunos as $inscricao)
                                        @include('reports.escola.partials.aluno-relatorio-row', ['inscricao' => $inscricao])
                                    @empty
                                        <tr>
                                            <td colspan="9">Nenhum aluno encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endforeach
            @endif
        @empty
            <tr>
                <td colspan="4">Nenhum registro encontrado.</td>
            </tr>
        @endforelse
    </tbody>
</table>
