<?php

namespace Tests\Unit;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\ObrasModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\TributosModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource;
use App\Filament\Egap\Support\ModalTable;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\TestCase;

class EgapModalTableTest extends TestCase
{
    public function test_property_modals_share_the_owner_table_configuration(): void
    {
        $tributosModal = new TributosModal;
        $tributosModal->mount(42);
        $tributosTable = $tributosModal->table(Table::make($tributosModal));

        $this->assertSame('Id_imovel', $tributosTable->getQuery()->getQuery()->wheres[0]['column']);
        $this->assertSame(42, $tributosTable->getQuery()->getQuery()->wheres[0]['value']);
        $this->assertSame(
            'Editar tributo do Imóvel #42',
            $tributosTable->getRecordActions()[0]->getModalHeading(),
        );

        $obrasModal = new ObrasModal;
        $obrasModal->mount(42);
        $obrasTable = $obrasModal->table(Table::make($obrasModal));

        $this->assertSame('id_imovel', $obrasTable->getQuery()->getQuery()->wheres[0]['column']);
    }

    public function test_it_configures_a_resource_table_for_an_owner_modal(): void
    {
        $livewire = Mockery::mock(HasTable::class);

        $table = ModalTable::make(
            table: Table::make($livewire),
            resource: TributoResource::class,
            foreignKey: 'Id_imovel',
            ownerId: 42,
            editModalHeading: 'Editar tributo do Imóvel #42',
        );

        $query = $table->getQuery();

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertSame('Id_imovel', $query->getQuery()->wheres[0]['column']);
        $this->assertSame(42, $query->getQuery()->wheres[0]['value']);

        [$editAction, $deleteAction] = $table->getRecordActions();

        $this->assertInstanceOf(EditAction::class, $editAction);
        $this->assertSame('Editar tributo do Imóvel #42', $editAction->getModalHeading());
        $this->assertInstanceOf(DeleteAction::class, $deleteAction);
        $this->assertInstanceOf(DeleteBulkAction::class, $table->getToolbarActions()[0]);
    }
}
