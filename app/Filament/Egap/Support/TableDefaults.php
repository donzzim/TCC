<?php

namespace App\Filament\Egap\Support;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class TableDefaults
{
    public static function apply(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'egap-table-defaults'], merge: true)
            ->emptyStateHeading('Nenhum registro encontrado')
            ->emptyStateDescription('')
            ->defaultPaginationPageOption(25)
            ->recordActions(self::actions())
            ->toolbarActions(self::bulkActions())
            ->paginated([25, 50, 100])
            ->striped();
    }

    public static function actions(): array
    {
        return [
            EditAction::make()
                ->tooltip('Editar')
                ->hiddenLabel(),
            ViewAction::make()
                ->tooltip('Visualizar')
                ->hiddenLabel(),
            DeleteAction::make()
                ->tooltip('Excluir')
                ->hiddenLabel(),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->label('Excluir'),
        ];
    }
}
