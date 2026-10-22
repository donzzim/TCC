<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components;

use App\Filament\Egap\Support\ModalTable;
use App\Filament\Egap\Support\TableModalComponent;
use Filament\Resources\Resource as FilamentResource;
use Filament\Tables\Table;

abstract class ImoveisModal extends TableModalComponent
{
    public int $imovelId;

    public function mount(int $imovelId): void
    {
        $this->imovelId = $imovelId;
    }

    final public function table(Table $table): Table
    {
        return $this->configureModalTable(ModalTable::make(
            table: $table,
            resource: $this->resource(),
            foreignKey: $this->foreignKey(),
            ownerId: $this->imovelId,
            editModalHeading: 'Editar '.$this->recordLabel().' do Imóvel #'.$this->imovelId,
        ));
    }

    /**
     * @return class-string<FilamentResource>
     */
    abstract protected function resource(): string;

    abstract protected function recordLabel(): string;

    protected function foreignKey(): string
    {
        return 'Id_imovel';
    }

    protected function configureModalTable(Table $table): Table
    {
        return $table;
    }
}
