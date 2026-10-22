<?php

namespace App\Filament\Resources\Contratacao\ProcessoResource\Pages;

use App\Filament\Resources\Contratacao\ProcessoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcesso extends EditRecord
{
    protected static string $resource = ProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
    public function getContentTabIcon(): ?string
    {
        return 'heroicon-m-pencil-square';
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
