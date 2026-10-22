<?php

namespace Tests\Feature\MobileApi;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Auth\MobileEgapSession;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\MobileSessao;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class MobileEgapSessionTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private function setorPrincipalEsperado($user): int
    {
        $linha = UserLotacao::query()->doUsuario($user->id)->first();

        return (int) $linha->setor_id;
    }

    private function tokenDe(string $plain): PersonalAccessToken
    {
        return PersonalAccessToken::findToken($plain);
    }

    public function test_inicia_no_setor_principal(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$sessao] = $this->sessaoPara($user);

        $this->assertSame($this->setorPrincipalEsperado($user), $sessao->setorAtual());
    }

    public function test_troca_setor_valido_persiste(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$sessao, $plain] = $this->sessaoPara($user);
        $ultima = $sessao->lotacoes()->last();

        $sessao->trocarSetor($ultima->id);

        $recarregada = MobileEgapSession::doToken($this->tokenDe($plain));
        $this->assertSame((int) $ultima->id, $recarregada->setorAtual());
        $this->assertSame((int) $ultima->codigodauo, $recarregada->unidadeAtual());
    }

    public function test_recusa_setor_alheio(): void
    {
        [$sessao] = $this->sessaoPara($this->usuarioComVariasLotacoes());

        $this->expectException(ValidationException::class);

        $sessao->trocarSetor(-1);
    }

    public function test_sessoes_de_dois_tokens_sao_independentes(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$a, $plainA] = $this->sessaoPara($user);
        [$b, $plainB] = $this->sessaoPara($user);
        $principal = $a->setorAtual();
        $outro = $a->lotacoes()->firstWhere('id', '!=', $principal);

        $a->trocarSetor($outro->id);

        $this->assertSame((int) $outro->id, MobileEgapSession::doToken($this->tokenDe($plainA))->setorAtual());
        $this->assertSame($principal, MobileEgapSession::doToken($this->tokenDe($plainB))->setorAtual());
    }

    public function test_nao_altera_a_sessao_web(): void
    {
        [$sessao] = $this->sessaoPara($this->usuarioComVariasLotacoes());

        $sessao->trocarSetor($sessao->lotacoes()->last()->id);

        $this->assertNull(session(EgapSession::SESSION_KEY_SETOR));
    }

    public function test_setor_removido_cai_para_principal(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$sessao, $plain] = $this->sessaoPara($user);
        $principal = $sessao->setorAtual();

        MobileSessao::query()->where('user_id', $user->id)->latest('id')->first()
            ->update(['setor_id' => 999999999]);

        $recarregada = MobileEgapSession::doToken($this->tokenDe($plain));

        $this->assertSame($principal, $recarregada->setorAtual());
        $this->assertSame($principal, MobileEgapSession::doToken($this->tokenDe($plain))->setorAtual());
    }

    public function test_usa_lotacao_legada_sem_user_lotacao(): void
    {
        $user = $this->usuarioSemUserLotacaoComLegado();
        [$sessao] = $this->sessaoPara($user);

        $legada = UserEgap::query()->find($sessao->resolverAtual())->lotacoes()->first();

        $this->assertTrue($sessao->usaLotacaoLegada());
        $this->assertSame((int) $legada->unidade_judiciaria, $sessao->escopo()['unidade_judiciaria']);
        $this->assertFalse($sessao->paraResposta()['lotacoes'][0]['principal']);
        $this->assertTrue($sessao->paraResposta()['lotacao_legada']);
    }

    public function test_escopo_e_nulo_sem_unidade_na_lotacao(): void
    {
        [$sessao] = $this->sessaoPara($this->usuarioComVariasLotacoes());

        foreach (['lotacao' => ['setor' => 1, 'codigodauo' => null], 'lotacaoResolvida' => true] as $prop => $valor) {
            $r = new ReflectionProperty($sessao, $prop);
            $r->setValue($sessao, $valor);
        }

        $this->assertNull($sessao->escopo());
    }

    public function test_escopo_tem_contrato_dos_services(): void
    {
        [$sessao] = $this->sessaoPara($this->usuarioComVariasLotacoes());

        $this->assertSame(['user_id', 'id_egap', 'setor', 'unidade_judiciaria'], array_keys($sessao->escopo()));
    }

    public function test_para_resposta_segue_o_contrato(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$sessao] = $this->sessaoPara($user);

        $r = $sessao->paraResposta();

        $this->assertSame(['usuario', 'egap_id', 'setor_atual', 'lotacoes', 'lotacao_legada'], array_keys($r));
        $this->assertSame(['id', 'nome', 'login', 'email', 'cpf', 'cargo'], array_keys($r['usuario']));
        $this->assertSame(['id', 'setor', 'unidade', 'codigodauo'], array_keys($r['setor_atual']));
        $this->assertSame(['id', 'setor', 'unidade', 'codigodauo', 'principal'], array_keys($r['lotacoes'][0]));
        $this->assertTrue($r['lotacoes'][0]['principal'] || ! UserLotacao::where('user_id', $user->id)->where('principal', 1)->exists());
        $this->assertFalse($r['lotacao_legada']);
    }

    public function test_encerrar_apaga_a_sessao(): void
    {
        [$sessao, $plain] = $this->sessaoPara($this->usuarioComVariasLotacoes());

        $sessao->encerrar();

        $this->assertNull(PersonalAccessToken::findToken($plain));
    }
}
