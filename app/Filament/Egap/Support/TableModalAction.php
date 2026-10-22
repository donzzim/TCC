<?php

namespace App\Filament\Egap\Support;

use Filament\Actions\Action;

class TableModalAction
{
    public const VIEW = 'egap.livewire.support.table-modal';

    public static function make(string $name): Action
    {
        return Action::make($name)
            ->modalWidth('full')
            ->extraModalWindowAttributes(ModalWindow::attributes())
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar');
    }
}
