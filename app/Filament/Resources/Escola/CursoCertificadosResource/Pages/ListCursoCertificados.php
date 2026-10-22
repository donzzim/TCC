<?php

namespace App\Filament\Resources\Escola\CursoCertificadosResource\Pages;

use App\Filament\Resources\Escola\CursoCertificadosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCursoCertificados extends ListRecords
{
    protected static string $resource = CursoCertificadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        if ($cursoId = request()->get('curso_id')) {
            $query->where('curso_id', $cursoId);
        }

        return $query;
    }
}
