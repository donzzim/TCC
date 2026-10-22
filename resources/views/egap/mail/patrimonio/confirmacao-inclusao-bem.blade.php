<p>Prezados,</p>

<p>Informamos que foi realizada uma solicitação de inclusão do bem abaixo no sistema. A Seção de Patrimônio verificará e responderá por e-mail oportunamente.</p>

<p>
    <strong>Nº Patrimônio:</strong> {{ $bem['NumPatrimonio'] }}<br>
    <strong>Descrição do Bem:</strong> {{ $bem['Descricao'] }}<br>
    <strong>Complemento do Setor:</strong> {{ $bem['ComplementoSetorDescricao'] ?? '-' }}<br>
    <strong>Estado de Conservação:</strong> {{ $bem['EstadodeConservacao'] }}
</p>

<p><strong>Setor Solicitante:</strong> {{ $setor }}</p>

<p>Atenciosamente,<br>
Seção de Patrimônio</p>
