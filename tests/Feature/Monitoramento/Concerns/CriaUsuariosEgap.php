<?php

namespace Tests\Feature\Monitoramento\Concerns;

use App\Models\egap\User;
use Illuminate\Support\Str;

trait CriaUsuariosEgap
{
    protected function usuarioEgap(?string $role = null): User
    {
        $sufixo = Str::lower(Str::random(10));

        $user = User::query()->create([
            'login' => "teste.{$sufixo}",
            'name' => "Usuário Teste {$sufixo}",
            'email' => "teste.{$sufixo}@example.invalid",
            'password' => bcrypt(Str::random(16)),
            'cpf' => '999'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'ativo' => 1,
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }
}
