<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class SetorSwitcher extends Component
{
    public ?int $setorAtual = null;
    public Collection $setores;

    /**
     * O método mount é executado quando o componente é inicializado.
     * Ele carrega os dados iniciais.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->setores = $user->setores;
        $setorPrincipalId = Session::get('setorprincipal');
        if (!$setorPrincipalId) {
            $setorPrincipalId = $user->setorPrincipal()->first()?->pivot->setor_id;
            if ($setorPrincipalId) {
                Session::put('setorprincipal', $setorPrincipalId);
            }
        }
        $this->setorAtual = Session::get('setorprincipal');
    }

    /**
     * Este é um "lifecycle hook" do Livewire.
     * Ele é executado AUTOMATICAMENTE toda vez que a propriedade pública '$setorAtual' é atualizada.
     */
    public function updatedSetorAtual(int $value): void
    {
        Session::put('setorprincipal', $value);
        $this->redirect(request()->header('Referer'));
    }
    public function render()
    {
        return view('livewire.setor-switcher');
    }
}
