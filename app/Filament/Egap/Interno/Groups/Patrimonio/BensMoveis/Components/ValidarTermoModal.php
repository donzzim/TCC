<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;

class ValidarTermoModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $termoId;

    public function mount(int $termoId): void
    {
        $this->termoId = $termoId;
    }

    public function table(Table $table): Table
    {
        return ValidarTermoResource::table($table)
            ->query(
                ValidarTermoResource::getEloquentQuery()->where('termo', $this->termoId)
            );
    }
}
