<?php

namespace Tests\Unit;

use App\Filament\Egap\Support\TableDefaults;
use Filament\Facades\Filament;
use Filament\Support\Assets\AssetManager;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Mockery;
use Tests\TestCase;

class EgapTableDefaultsTest extends TestCase
{
    public function test_egap_panel_registers_the_table_stylesheet(): void
    {
        Filament::getPanel('egap')->registerAssets();

        $style = collect(app(AssetManager::class)->getStyles(['app']))
            ->first(fn ($style): bool => $style->getId() === 'table');

        $this->assertNotNull($style);
        $this->assertSame(resource_path('css/filament/egap/table.css'), $style->getPath());
        $this->assertFileExists($style->getPublicPath());
    }

    public function test_table_defaults_adds_the_table_styles_scope(): void
    {
        $livewire = Mockery::mock(HasTable::class);
        $table = TableDefaults::apply(Table::make($livewire));

        $this->assertSame('egap-table-defaults', $table->getExtraAttributeBag()->get('class'));
    }
}
