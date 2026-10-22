<p>Informamos que o pedido foi analisado e teve seu status alterado, conforme informações abaixo.</p>

<p>
    Pedido No: <b>{{ $item->idPedido }}/{{ $item->idPedidoRef->date_time->format('Y') }}</b><br>
    Situação: <b>{{ $item->situacaoRef->Descricao }}</b><br>
    Material: <b>{{ $item->descricaoDetalhadaRef->descricao_detalhada }}</b>
</p>

@if (filled($item->ObservacaoItem))
<p>{{ $item->ObservacaoItem }}</p>
@endif

<p><b>Seção de Patrimônio do TJES</b></p>
