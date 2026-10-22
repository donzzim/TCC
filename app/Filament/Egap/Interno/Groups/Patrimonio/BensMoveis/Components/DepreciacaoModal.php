<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;

class DepreciacaoModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $bemMovelId;

    public function mount(int $bemMovelId): void
    {
        $this->bemMovelId = $bemMovelId;

        $this->tableFilters = [
            'patrimonio' => ['patrimonio' => $this->bemMovelId],
        ];
    }

    public function table(Table $table): Table
    {
        return DepreciacaoResource::table($table)
            ->query(
                DepreciacaoResource::getEloquentQuery()
                    ->where('patrimonio', $this->bemMovelId)
            );
    }
}
