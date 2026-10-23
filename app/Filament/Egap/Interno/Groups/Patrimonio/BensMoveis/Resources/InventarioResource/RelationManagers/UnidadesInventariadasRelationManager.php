<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\RelationManagers;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource;
use App\Filament\Egap\Interno\Permissions\Patrimonio\InventarioAccess;
use App\Filament\Egap\Support\TableColumns;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Unidades do inventário numa tabela paginada (com modal por unidade). Antes
 * ficavam num Repeater dentro do formulário do inventário, que com centenas de
 * unidades estourava a memória ao abrir a edição.
 */
class UnidadesInventariadasRelationManager extends RelationManager
{
    protected static string $relationship = 'unidadesInventariadas';

    protected static ?string $title = 'Unidades inventariadas';

    protected static ?string $modelLabel = 'unidade inventariada';

    protected static ?string $pluralModelLabel = 'unidades inventariadas';

    public function isReadOnly(): bool
    {
        return ! InventarioAccess::podeAbrir();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(InventarioResource::camposUnidadeInventariada());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with('unidade.pai')->withCount('equipes'))
            ->columns([
                TableColumns::text('unidade_inventariada', 'Unidade/Setor', isFirstColumn: true)
                    ->state(fn(InventarioUnidade $record): string => $record->rotuloUnidadeInventariada())
                    ->searchable(false)
                    ->sortable(false)
                    ->wrap(),
                TableColumns::date('data_inicio', 'Início'),
                TableColumns::date('data_termino', 'Término'),
                TableColumns::text('dias', 'Dias')->suffix(' dias'),
                TableColumns::text('situacao', 'Situação')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Inventario::rotuloSituacao($state))
                    ->color(fn(?string $state): string => Inventario::corSituacao($state)),
                TableColumns::text('equipes_count', 'Integrantes')
                    ->searchable(false)
                    ->badge()
                    ->color('gray'),
            ])
            ->headerActions([
                CreateAction::make()->label('Adicionar unidade'),
            ])
            ->recordActions([
                EditAction::make()->hiddenLabel()->tooltip('Editar'),
                DeleteAction::make()->hiddenLabel()->tooltip('Excluir'),
            ])
            ->defaultSort('id')
            ->paginated([10, 25, 50]);
    }
}
