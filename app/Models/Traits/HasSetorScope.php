<?php

namespace App\Models\Traits;

use App\Models\Scopes\SetorScope;

trait HasSetorScope
{
    protected static function bootHasSetorScope(): void
    {
        // dd('Método booted da Trait HasSetorScope foi chamado'); // DEBUG
        static::addGlobalScope(new SetorScope);
    }
}
