<?php

namespace App\Filament\Resources\Escola\AcaoDespesasResource\Pages;

use App\Filament\Resources\Escola\AcaoDespesasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAcaoDespesas extends ListRecords
{
    protected static string $resource = AcaoDespesasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        if ($acaoId = request()->get('acao_id')) {
            $query->where('acao_id', $acaoId);
        }

        return $query;
    }
}
