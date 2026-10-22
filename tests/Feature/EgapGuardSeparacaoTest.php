<?php

namespace Tests\Feature;

use App\Models\egap\User as EgapUser;
use App\Models\User as EmesUser;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * EGAP e EMES são painéis independentes: guard, model e papéis próprios.
 * Roda contra o banco real, somente leitura.
 */
class EgapGuardSeparacaoTest extends TestCase
{
    public function test_egap_access_comes_from_egap_roles_only(): void
    {
        $user = $this->egapUserComRole('egap_user');

        $this->assertTrue($user->hasRole('egap_user'));
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('egap')));

        // o mesmo users.id, visto pelo model do EMES, não carrega o papel do EGAP
        $this->assertFalse(EmesUser::find($user->id)->hasRole('egap_user'));
    }

    public function test_an_emes_only_user_cannot_enter_egap(): void
    {
        $id = EmesUser::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'portal_user'))
            ->whereNotIn('id', EgapUser::query()->whereHas('roles')->select('id'))
            ->value('id');

        if (! $id) {
            $this->markTestSkipped('nenhum usuário só do EMES neste banco');
        }

        $this->assertFalse(EgapUser::find($id)->canAccessPanel(Filament::getPanel('egap')));
    }

    public function test_super_admin_enters_both_panels(): void
    {
        $user = $this->egapUserComRole('egap_super_admin');

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('egap')));
        $this->assertTrue(EmesUser::find($user->id)->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_each_panel_has_its_own_super_admin_role(): void
    {
        $user = $this->egapUserComRole('egap_super_admin');

        $this->assertTrue($user->isSuperAdminEgap());
        $this->assertTrue($user->isAdminEgap());
        $this->assertFalse($user->hasRole('super_admin'), 'o super_admin do EMES não vale no EGAP');

        $emes = EmesUser::find($user->id);
        $this->assertTrue($emes->hasRole('super_admin'));
        $this->assertFalse($emes->hasRole('egap_super_admin'), 'o super admin do EGAP não vale no EMES');
    }

    public function test_a_web_session_does_not_open_the_egap_panel(): void
    {
        $user = $this->egapUserComRole('egap_user');

        $this->actingAs(EmesUser::find($user->id), 'web')
            ->get('/egap')
            ->assertRedirect(route('filament.egap.auth.login'));
    }

    public function test_the_egap_session_opens_the_egap_panel(): void
    {
        $user = $this->egapUserComRole('egap_user');

        $this->actingAs($user, EgapUser::GUARD)
            ->get('/egap')
            ->assertSuccessful();
    }

    public function test_egap_logout_keeps_the_emes_login(): void
    {
        $user = $this->egapUserComRole('egap_super_admin');
        [$web, $egap] = $this->chavesDeLogin();

        $this->withSession([$web => $user->id, $egap => $user->id, 'egap_session' => ['user_id' => $user->id]])
            ->post(route('filament.egap.auth.logout'))
            ->assertRedirect()
            ->assertSessionHas($web, $user->id)
            ->assertSessionMissing($egap)
            ->assertSessionMissing('egap_session');
    }

    public function test_emes_logout_keeps_its_original_behaviour(): void
    {
        $user = $this->egapUserComRole('egap_super_admin');
        [$web, $egap] = $this->chavesDeLogin();

        // o logout do EMES continua invalidando a sessão inteira
        $this->withSession([$web => $user->id, $egap => $user->id])
            ->post(route('filament.admin.auth.logout'))
            ->assertRedirect()
            ->assertSessionMissing($web)
            ->assertSessionMissing($egap);
    }

    public function test_a_session_that_lost_access_is_sent_back_to_login_instead_of_403(): void
    {
        $id = EmesUser::query()
            ->whereNotIn('id', EgapUser::query()->whereHas('roles')->select('id'))
            ->value('id');
        [$web, $egap] = $this->chavesDeLogin();

        // logado no guard egap, mas sem nenhum papel do EGAP (ex.: papel removido)
        $this->withSession([$web => $id, $egap => $id])
            ->get('/egap')
            ->assertRedirect(route('filament.egap.auth.login'))
            ->assertSessionMissing($egap)
            ->assertSessionHas($web, $id);
    }

    public function test_admin_pages_render_with_egap_models_only(): void
    {
        $user = $this->egapUserComRole('egap_super_admin');

        $this->actingAs($user, EgapUser::GUARD)
            ->get(route('filament.egap.admin-egap.pages.users-egap'))
            ->assertSuccessful();

        $this->actingAs($user, EgapUser::GUARD)
            ->get(route('filament.egap.admin-egap.pages.users-migration'))
            ->assertSuccessful();

        // resource de papéis do Shield fica fora enquanto o plugin está desligado
        $this->actingAs($user, EgapUser::GUARD)
            ->get(route('filament.egap.admin-egap.resources.shield.roles.index'))
            ->assertForbidden();
    }

    public function test_print_routes_require_the_egap_login(): void
    {
        $this->get('/egap/relatorios-gerais/imprimir')
            ->assertRedirect(route('filament.egap.auth.login'));
    }

    /**
     * Chaves que o SessionGuard grava na sessão para `web` e `egap`.
     *
     * @return array{0: string, 1: string}
     */
    private function chavesDeLogin(): array
    {
        return [Auth::guard('web')->getName(), Auth::guard(EgapUser::GUARD)->getName()];
    }

    private function egapUserComRole(string $role): EgapUser
    {
        $user = EgapUser::query()
            ->whereHas('roles', fn ($q) => $q->where('name', $role))
            ->whereNotNull('email_verified_at')
            ->first();

        if (! $user) {
            $this->markTestSkipped("nenhum usuário EGAP com {$role} neste banco");
        }

        return $user;
    }
}
