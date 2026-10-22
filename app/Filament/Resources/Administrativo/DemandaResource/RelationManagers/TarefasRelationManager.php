<?php

namespace App\Filament\Resources\Administrativo\DemandaResource\RelationManagers;

use BackedEnum;
use App\Filament\Resources\Administrativo\TarefaResource;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TarefasRelationManager extends RelationManager
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';
    protected static string $relationship = 'tarefas';

    public function form(Schema $schema): Schema
    {
        return TarefaResource::form($form);
    }

    public function table(Table $table): Table
    {
        return TarefaResource::table($table);
    }
}
