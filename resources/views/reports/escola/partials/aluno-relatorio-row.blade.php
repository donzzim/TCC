@php
    $aluno = $inscricao->aluno;
    $perfil = $aluno?->perfil;
    $recadastro = $aluno?->recadastro;
    $cargo = $recadastro?->cargo ?: ($recadastro?->funcao ?: ($recadastro?->tipovinc ?: ($perfil?->cargo ?: 'Público Externo')));
    $setorLotacao = $aluno?->setorPrincipal?->first() ?: $aluno?->setores?->first();
    $setor = $setorLotacao?->setor ?? 'N/D';
@endphp

<tr>
    <td class="text-center">{{ $aluno?->cpf ?? 'N/D' }}</td>
    <td>{{ $aluno?->name ?? 'N/D' }}</td>
    <td>{{ $aluno?->email ?? 'N/D' }}</td>
    <td>{{ $perfil?->tipoPessoa?->descricao ?? 'N/D' }}</td>
    <td>{{ $perfil?->sexo_descricao ?? 'N/D' }}</td>
    <td>{{ $perfil?->raca_descricao ?? 'N/D' }}</td>
    <td>{{ $perfil?->pcd_formatado ?? 'Sem deficiência' }}</td>
    <td>{{ $cargo }}</td>
    <td>{{ $setor }}</td>
</tr>
