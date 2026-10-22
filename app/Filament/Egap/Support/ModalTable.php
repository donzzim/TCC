<?php

namespace App\Filament\Egap\Support;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ModalTable
{
    /**
     * @param  class-string<FilamentResource>  $resource
     */
    public static function make(
        Table $table,
        string $resource,
        string $foreignKey,
        int|string $ownerId,
        string $editModalHeading,
    ): Table {
        return $resource::table($table)
            ->query(
                $resource::getEloquentQuery()
                    ->where($foreignKey, $ownerId)
            )
            ->recordActions([
                EditAction::make()
                    ->schema(fn (Schema $schema): Schema => $resource::form($schema))
                    ->tooltip('Editar')
                    ->modalHeading($editModalHeading)
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Excluir'),
            ]);
    }
}
