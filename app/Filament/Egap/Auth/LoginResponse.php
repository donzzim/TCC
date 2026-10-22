<?php

namespace App\Filament\Egap\Auth;

use Livewire\Component;

class LoginResponse implements \Filament\Auth\Http\Responses\Contracts\LoginResponse
{
    public function toResponse($request)
    {
        session()->flash('egap_boas_vindas', true);

        return redirect()->intended(filament()->getUrl());
    }
}
