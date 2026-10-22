<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCedido extends CreateRecord
{
    protected static string $resource = CedidoResource::class;

    protected ?string $heading = 'Adicionar Imóveis Ocupados por Terceiros';
}
