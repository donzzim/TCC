<?php

namespace App\Filament\Resources\Administrativo\DemandaResource\RelationManagers;

use BackedEnum;
use App\Filament\Resources\Escola\CursosResource;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CursosRelationManager extends RelationManager
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected static string $relationship = 'cursos';

    public function form(Schema $schema): Schema
    {
        return CursosResource::form($form);
    }

    public function table(Table $table): Table
    {
        return CursosResource::table($table);
    }
}
