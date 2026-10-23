<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource;
use App\Services\Egap\Inventario\AbrangenciaInventario;
use Filament\Resources\Pages\CreateRecord;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected function afterCreate(): void
    {
        $dados = $this->form->getRawState();

        AbrangenciaInventario::registrar(
            $this->getRecord(),
            array_values($dados['selecao_unidades'] ?? []),
            array_values(array_filter(
                $dados['selecao_setores'] ?? [],
                fn (array $item): bool => filled($item['setor_id'] ?? null),
            )),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
