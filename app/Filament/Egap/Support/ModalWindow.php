<?php

namespace App\Filament\Egap\Support;

class ModalWindow
{
    public static function attributes(): array
    {
        return [
            'class' => 'egap-modal-window',
            'style' => 'width: calc(100vw - 2rem); max-width: 96rem; height: min(82dvh, 860px); overflow: hidden;',
        ];
    }
}
