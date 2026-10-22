<?php

namespace App\Filament\Egap\Support;

use Filament\Actions\CreateAction;

class CreateActionModal
{
    public static function createAction(): CreateAction
    {
        return CreateAction::make()
            ->label('Novo')
            ->modalHeading(' ');
    }
}
