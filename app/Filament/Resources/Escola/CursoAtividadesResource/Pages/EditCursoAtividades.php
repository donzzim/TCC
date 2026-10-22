<?php

namespace App\Filament\Resources\Escola\CursoAtividadesResource\Pages;

use App\Filament\Resources\Escola\CursoAtividadesResource;
use App\Models\Administrativo\Agenda;
use Exception;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditCursoAtividades extends EditRecord
{
    protected static string $resource = CursoAtividadesResource::class;



    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
