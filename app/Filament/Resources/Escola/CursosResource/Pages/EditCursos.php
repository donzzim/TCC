<?php

namespace App\Filament\Resources\Escola\CursosResource\Pages;

use App\Filament\Resources\Escola\CursosResource;
use App\Models\Administrativo\Agenda;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditCursos extends EditRecord
{
    protected static string $resource = CursosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }
}
