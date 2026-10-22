<?php

namespace Tests\Unit;

use App\Filament\Egap\Support\SetoresSelects;
use Filament\Forms\Components\Select;
use Tests\TestCase;

class EgapSetoresSelectsTest extends TestCase
{
    public function test_default_emes_selects_share_the_standard_configuration(): void
    {
        $unidade = SetoresSelects::unidade();
        $setor = SetoresSelects::setor();

        $this->assertStandardUnidade($unidade, 'unidade_judiciaria');
        $this->assertStandardSetor($setor, 'setor_id');
    }

    public function test_egap_selects_accept_legacy_field_names(): void
    {
        $unidade = SetoresSelects::unidadeEgap(
            'UnidadeAtual',
            'SetorAtual',
            SetoresSelects::EGAP_UNIDADES_INVENTARIAVEIS,
            'Setor',
            'id',
        );
        $setor = SetoresSelects::setorEgap('SetorAtual', 'UnidadeAtual');

        $this->assertStandardUnidade($unidade, 'UnidadeAtual');
        $this->assertStandardSetor($setor, 'SetorAtual');
    }

    private function assertStandardUnidade(Select $select, string $name): void
    {
        $this->assertSame($name, $select->getName());
        $this->assertSame('Unidade Judiciária', $select->getLabel());
        $this->assertSame('Selecione a unidade judiciária', $select->getPlaceholder());
        $this->assertTrue($select->isRequired());
        $this->assertTrue($select->isSearchable());
        $this->assertTrue($select->isPreloaded());
        $this->assertTrue($select->isLive());
        $this->assertFalse($select->isNative());
    }

    private function assertStandardSetor(Select $select, string $name): void
    {
        $this->assertSame($name, $select->getName());
        $this->assertSame('Setor', $select->getLabel());
        $this->assertTrue($select->isRequired());
        $this->assertTrue($select->isSearchable());
        $this->assertTrue($select->isPreloaded());
        $this->assertFalse($select->isNative());
    }
}
