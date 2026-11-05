<?php

namespace App\Monitoramento;

use App\Models\egap\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class AcessoAoMonitoramento
{
    public static function permitido(?Authenticatable $u): bool
    {
        if (! config('monitoramento.ativo') || ! $u instanceof User) {
            return false;
        }

        if (! $u->isAdminEgap() && ! $u->hasRole(User::ROLE_ADMIN)) {
            return false;
        }

        return in_array(strtolower(trim((string) $u->email)), (array) config('monitoramento.devs', []), true);
    }
}
