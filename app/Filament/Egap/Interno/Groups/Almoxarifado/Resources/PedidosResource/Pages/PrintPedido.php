<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource;
use App\Models\egap\Almoxarifado\Pedidos;
use Filament\Resources\Pages\Page;

class PrintPedido extends Page
{
    protected static string $resource = PedidosResource::class;

    //    protected string $view = 'egap.almoxarifado.pedidos.print-page';

    public Pedidos $record;

    public function mount(Pedidos $record): void
    {
        $this->record = $record->load([
            'solicitante_get',
            'responsavel_atendimento',
            'setor_get',
            'complementoSetor',
            'itens.materialRef',
            'itens.descricaoDetalhadaRef',
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'pedido' => $this->record,
        ];
    }
}
