<?php

namespace App\Filament\Resources\Escola\SubmissionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Escola\SubmissionResource;
use Illuminate\Database\Eloquent\Builder;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        if ($cursoId = request()->get('curso_id')) {
            $query->where('curso_id', $cursoId);
        }

        return $query;
    }
}
