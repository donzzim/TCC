<?php

namespace Tests\Feature;

use App\Models\egap\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * A área externa (prefixo egap/externo) só abre para quem tem a role
 * `egap_externo` ou é admin do EGAP; para os demais, nem o link da topbar
 * aparece.
 *
 * Roda contra o banco real; a role concedida no teste positivo é desfeita
 * pela transação.
 */
class EgapAcessoAreaExternaTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_usuario_sem_role_externo_recebe_403_e_nao_ve_o_link(): void
    {
        $user = $this->usuarioSemAcessoExterno();

        $this->actingAs($user, User::GUARD);

        $this->get('/egap/externo/home')->assertForbidden();
        $this->get('/egap/externo')->assertForbidden();
        $this->followingRedirects()->get('/egap')->assertOk()->assertDontSee('Área Externa');
    }

    public function test_usuario_com_role_externo_acessa_e_ve_o_link(): void
    {
        $user = $this->usuarioSemAcessoExterno();

        Role::findOrCreate(User::ROLE_EXTERNO, User::GUARD);
        $user->assignRole(User::ROLE_EXTERNO);

        $this->actingAs($user, User::GUARD);

        $this->get('/egap/externo/home')->assertOk();
        $this->followingRedirects()->get('/egap')->assertOk()->assertSee('Área Externa');
    }

    private function usuarioSemAcessoExterno(): User
    {
        $user = User::query()
            ->where('ativo', 1)
            ->whereHas('setores')
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', [User::ROLE_EXTERNO, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]))
            ->get()
            ->first(fn (User $u) => $u->can('egap_access'));

        if (! $user) {
            $this->markTestSkipped('nenhum usuário do EGAP, com lotação, sem acesso à área externa neste banco');
        }

        return $user;
    }
}
