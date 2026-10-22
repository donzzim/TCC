<p>Prezados,</p>

<p>Gostaríamos de solicitar que o bem descrito abaixo seja cadastrado no setor <strong>{{ $setor }}</strong>.</p>

<p>
    <strong>Nº Patrimônio:</strong> {{ $bem['NumPatrimonio'] }}<br>
    @if (! empty($bem['NumPatrimonioAntigo']))
        <strong>Nº Patrimônio Antigo:</strong> {{ $bem['NumPatrimonioAntigo'] }}<br>
    @endif
    <strong>Descrição do Bem:</strong> {{ $bem['Descricao'] }}<br>
    @if (! empty($bem['NumeroSerie']))
        <strong>Nº Série:</strong> {{ $bem['NumeroSerie'] }}<br>
    @endif
    @if (! empty($bem['Marca']))
        <strong>Marca:</strong> {{ $bem['Marca'] }}<br>
    @endif
    @if (! empty($bem['Modelo']))
        <strong>Modelo:</strong> {{ $bem['Modelo'] }}<br>
    @endif
    <strong>Complemento do Setor:</strong> {{ $bem['ComplementoSetorDescricao'] ?? '-' }}<br>
    <strong>Estado de Conservação:</strong> {{ $bem['EstadodeConservacao'] }}
</p>

<p><strong>Setor Solicitante:</strong> {{ $setor }}<br>
<strong>Solicitado por:</strong> {{ $solicitante }}</p>

<p>Atenciosamente,<br>
Sistema e-GAP</p>
