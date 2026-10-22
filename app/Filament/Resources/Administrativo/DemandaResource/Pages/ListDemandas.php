<?php

namespace App\Filament\Resources\Administrativo\DemandaResource\Pages;

use App\Filament\Resources\Administrativo\DemandaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDemandas extends ListRecords
{
    protected static string $resource = DemandaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        if ($id = request()->get('demanda_id')) {
            $query->where('id', $id);
        }

        return $query;
    }
}
