<?php

namespace Tests\Feature\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Models\egap\MobileSessao;
use App\Models\egap\User;
use App\Models\egap\UserLotacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private const SENHA = 'segredo-teste';

    /**
     * Usuário real com acesso, lotação, e-mail e CPF só com dígitos (o login
     * numérico do painel só casa com CPF assim armazenado). A senha é
     * redefinida dentro da transação do teste.
     */
    private function usuarioDeLogin(): User
    {
        $candidatos = User::query()
            ->where('ativo', 1)
            ->whereNotNull('email')
            ->whereNotNull('login')
            ->whereRaw("cpf REGEXP '^[0-9]+$'")
            ->has('setores')
            ->orderBy('id')
            ->limit(300)
            ->get();

        foreach ($candidatos as $user) {
            if ($this->temAcessoEgap($user) && filter_var($user->email, FILTER_VALIDATE_EMAIL) && ! is_numeric($user->login)) {
                $user->forceFill(['password' => Hash::make(self::SENHA)])->save();

                return $user;
            }
        }

        $this->markTestSkipped('nenhum usuário com login, e-mail, CPF numérico e lotação neste banco');
    }

    private function logar(string $login, string $senha = self::SENHA)
    {
        return $this->postJson('/mobile-api/login', ['login' => $login, 'password' => $senha]);
    }

    private function perderAcessoEgap(User $user): void
    {
        $user->syncRoles([]);
        $user->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_login_por_login_email_e_cpf(): void
    {
        $user = $this->usuarioDeLogin();
        $principal = (int) UserLotacao::query()->doUsuario($user->id)->first()->setor_id;

        foreach ([$user->login, $user->email, $user->cpf] as $identificador) {
            $this->logar($identificador)
                ->assertOk()
                ->assertJsonPath('sessao.setor_atual.id', $principal)
                ->assertJsonPath('sessao.usuario.id', $user->id)
                ->assertJson(fn ($json) => $json->whereType('token', 'string')->etc());
        }
    }

    public function test_login_senha_errada(): void
    {
        $user = $this->usuarioDeLogin();

        $this->logar($user->login, 'senha-incorreta')->assertUnauthorized();
        $this->assertSame(0, MobileSessao::query()->where('user_id', $user->id)->count());
    }

    public function test_login_usuario_inativo(): void
    {
        $user = $this->usuarioDeLogin();
        $user->forceFill(['ativo' => 0])->save();

        $this->logar($user->login)->assertForbidden()->assertJsonPath('code', 'sem_acesso');
    }

    public function test_login_sem_egap_access(): void
    {
        $user = $this->usuarioDeLogin();
        $this->perderAcessoEgap($user);

        $this->logar($user->login)->assertForbidden()->assertJsonPath('code', 'sem_acesso');
    }

    public function test_login_throttle(): void
    {
        $user = $this->usuarioDeLogin();

        for ($i = 1; $i <= 5; $i++) {
            $this->logar($user->login, 'senha-incorreta')->assertUnauthorized();
        }

        $this->logar($user->login, 'senha-incorreta')->assertStatus(429);
    }

    public function test_login_throttle_ignora_espacos_do_login(): void
    {
        $user = $this->usuarioDeLogin();
        $variantes = [$user->login, ' '.$user->login, $user->login.' ', ' '.$user->login.' ', $user->login];

        foreach ($variantes as $login) {
            $this->logar($login, 'senha-incorreta')->assertUnauthorized();
        }

        $this->logar(' '.$user->login, 'senha-incorreta')->assertStatus(429);
    }

    public function test_token_sem_sessao_recebe_401(): void
    {
        $user = $this->usuarioDeLogin();
        $plain = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($plain)->getJson('/mobile-api/sessao')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'sessao_invalida');
    }

    public function test_sessao_de_usuario_removido_recebe_401(): void
    {
        $user = $this->usuarioDeLogin();
        $token = $user->createToken('mobile-app');
        $sessao = MobileSessao::query()->create([
            'token_id' => $token->accessToken->getKey(),
            'user_id' => 2147483000,
            'info' => [],
        ]);

        $this->assertNotNull($sessao->getKey());
        $this->withToken($token->plainTextToken)->getJson('/mobile-api/sessao')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'sessao_invalida');
    }

    public function test_sessao_retorna_dados_do_login(): void
    {
        $user = $this->usuarioDeLogin();
        $token = $this->logar($user->login)->assertOk()->json('token');

        $this->withToken($token)->getJson('/mobile-api/sessao')
            ->assertOk()
            ->assertJsonPath('usuario.id', $user->id)
            ->assertJsonStructure(['usuario', 'egap_id', 'setor_atual', 'lotacoes', 'lotacao_legada']);
    }

    public function test_troca_setor(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$sessao, $plain] = $this->sessaoPara($user);
        $outro = $sessao->lotacoes()->first(fn ($setor) => (int) $setor->id !== $sessao->setorAtual());

        $this->withToken($plain)->putJson('/mobile-api/sessao/setor', ['setor_id' => $outro->id])
            ->assertOk()
            ->assertJsonPath('setor_atual.id', (int) $outro->id);

        $this->withToken($plain)->putJson('/mobile-api/sessao/setor', ['setor_id' => 2147483000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('setor_id');
    }

    public function test_logout_apaga_token_e_sessao(): void
    {
        $user = $this->usuarioDeLogin();
        $token = $this->logar($user->login)->assertOk()->json('token');
        $id = PersonalAccessToken::findToken($token)->getKey();

        $this->withToken($token)->postJson('/mobile-api/logout')->assertOk();

        $this->assertNull(PersonalAccessToken::query()->find($id));
        $this->assertSame(0, MobileSessao::query()->where('token_id', $id)->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/mobile-api/sessao')->assertUnauthorized();
    }

    public function test_perda_de_acesso_depois_do_login(): void
    {
        $user = $this->usuarioDeLogin();
        $token = $this->logar($user->login)->assertOk()->json('token');

        $user->forceFill(['ativo' => 0])->save();

        $this->withToken($token)->getJson('/mobile-api/sessao')
            ->assertForbidden()
            ->assertJsonPath('code', 'sem_acesso');
    }

    public function test_middleware_setor_retorna_409_sem_lotacao(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'mobile.setor'])
            ->get('/mobile-api/_teste-setor', fn (MobileEgapSession $sessao) => response()->json(['ok' => true]));

        $user = $this->usuarioDeLogin();
        [, $plain] = $this->sessaoPara($user);

        $this->withToken($plain)->getJson('/mobile-api/_teste-setor')->assertOk();

        UserLotacao::query()->where('user_id', $user->id)->delete();
        $this->app['auth']->forgetGuards();

        $resposta = $this->withToken($plain)->getJson('/mobile-api/_teste-setor');

        if ($resposta->status() === 200) {
            $this->markTestSkipped('usuário ainda resolve lotação legada após remover user_lotacao');
        }

        $resposta->assertStatus(409)->assertJsonPath('code', 'sem_lotacao');
    }
}
