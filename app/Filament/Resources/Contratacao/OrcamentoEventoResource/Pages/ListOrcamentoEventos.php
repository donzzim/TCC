<?php

namespace App\Filament\Resources\Contratacao\OrcamentoEventoResource\Pages;

use App\Filament\Resources\Contratacao\OrcamentoEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrcamentoEventos extends ListRecords
{
    protected static string $resource = OrcamentoEventoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('remanejamento')
                ->label('Remanejamento')
                ->color('warning')
                ->size('sm')
                ->icon('heroicon-o-arrow-path')
                ->action(function (array $data): void {
                    OrcamentoEventoResource::criarRemanejamento($data);
                })
                ->form(
                    OrcamentoEventoResource::getFormfieldsRemanejamento(),
                )
                ->slideOver(),
        ];
    }
}
