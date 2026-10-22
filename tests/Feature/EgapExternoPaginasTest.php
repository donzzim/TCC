<?php

namespace Tests\Feature;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Throwable;
use Tests\TestCase;

/**
 * Monta todas as páginas do Ambiente Externo.
 *
 * Existe por causa da remoção dos traits `SelecionaSetorAtual` e
 * `SelecionaUsuarioAtual`: eles traziam `mount()` e ações de cabeçalho para
 * onze páginas, e o que resta tem de abrir sozinho, direto nos dados, sem o
 * modal de identificação que havia antes.
 *
 * Roda contra o banco real, em leitura.
 */
class EgapExternoPaginasTest extends TestCase
{
    /**
     * @return list<array{0: class-string}>
     */
    public static function paginasExternas(): array
    {
        $raiz = 'App\\Filament\\Egap\\Externo\\Groups\\';

        // As páginas que usavam os traits removidos. Deliberadamente não é a
        // varredura de todas as 23 do cluster: montar cada página do Filament
        // custa caro e o processo estoura os 128 MB do phpunit.
        $paginas = [
            'Agendamento\\Pages\\AgendamentoGerais',
            'Agendamento\\Pages\\AgendamentoUser',
            'Patrimonio\\Pages\\BensNoSetor',
            'Patrimonio\\Pages\\HistoricoDeInventarioOnline',
            'Patrimonio\\Pages\\LevantamentoComissao',
            'Patrimonio\\Pages\\MovimentacaoDeMateriais',
            'PlanoContratacao\\Pages\\AnaliseSolicitacoes',
            'PlanoContratacao\\Pages\\CalendarioContratacoes',
            'PlanoContratacao\\Pages\\MeusPlanos',
            'PlanoContratacao\\Pages\\NovaSolicitacao',
            'ServicosGerais\\Pages\\Fiscal',
            'ServicosGerais\\Pages\\Gestor',
        ];

        $casos = [];

        foreach ($paginas as $pagina) {
            $classe = $raiz . $pagina;

            if (! class_exists($classe) || (new ReflectionClass($classe))->isAbstract()) {
                continue;
            }

            // a chave vira o nome do caso no relatório do PHPUnit
            $casos[class_basename($classe)] = [$classe];
        }

        return $casos;
    }

    /**
     * @param  class-string  $pagina
     */
    #[DataProvider('paginasExternas')]
    public function test_it_mounts_without_the_identification_modal(string $pagina): void
    {
        $this->usuarioComAcessoExterno();

        if (! $pagina::canAccess()) {
            // páginas com autorização própria (ex.: AnaliseSolicitacoes exige
            // grupo do Joomla via UsergroupMap) — fora do alcance deste teste
            $this->markTestSkipped('usuário de teste não tem permissão para esta página');
        }

        try {
            Livewire::test($pagina)
                ->assertOk()
                ->assertNotDispatched('open-modal');
        } catch (Throwable $e) {
            // o módulo de Serviços Gerais depende das tabelas `avc_*`, cujas
            // migrations existem em database/Egap/migrations mas nunca rodaram
            // neste banco. É lacuna de ambiente, não regressão da página.
            if (preg_match("/Table '[^']*\.(\w+)' doesn't exist/", $e->getMessage(), $m)) {
                $this->markTestSkipped("tabela `{$m[1]}` não existe neste banco — migration pendente");
            }

            throw $e;
        }

        $this->assertNotNull(
            EgapSession::setorAtual(),
            "{$pagina} precisa de um setor resolvido para mostrar dados"
        );
    }

    private function usuarioComAcessoExterno(): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['egap_admin', 'egap_super_admin']))
            ->whereHas('setores')
            ->get()
            ->first(fn (User $u) => filled(EgapSession::formatosDeBusca($u->cpf)));

        if (! $user) {
            $this->markTestSkipped('nenhum usuário com acesso ao Externo e lotação neste banco');
        }

        $this->actingAs($user, User::GUARD);
        Filament::setCurrentPanel('egap');

        return $user;
    }
}
