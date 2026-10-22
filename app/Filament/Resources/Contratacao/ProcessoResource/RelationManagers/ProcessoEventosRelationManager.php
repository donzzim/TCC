<?php

namespace App\Filament\Resources\Contratacao\ProcessoResource\RelationManagers;

use BackedEnum;
use App\Filament\Resources\Contratacao\ProcessoEventoResource;
use App\Models\Contratacao\ProcessoEvento;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProcessoEventosRelationManager extends RelationManager
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $badge = 'new';
    protected static string $relationship = 'processo_eventos';
    public function form(Schema $schema): Schema
    {
        return ProcessoEventoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ProcessoEventoResource::table($table);
    }
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = number_format(ProcessoEvento::where('processo_id', '=', $ownerRecord->id)->count(), 0, ',', '.');
        return $count;
    }
}
