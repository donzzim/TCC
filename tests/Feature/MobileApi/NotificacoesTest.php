<?php

namespace Tests\Feature\MobileApi;

use App\Models\egap\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class NotificacoesTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    /**
     * @return array<int, User>
     */
    private function usuarios(int $quantos): array
    {
        $achados = [];

        foreach (User::query()->where('ativo', 1)->orderBy('id')->limit(300)->get() as $user) {
            if ($this->temAcessoEgap($user)) {
                $achados[] = $user;
            }

            if (count($achados) === $quantos) {
                return $achados;
            }
        }

        $this->markTestSkipped('usuários com egap_access insuficientes neste banco');
    }

    private function criar(User $user, array $dados = [], bool $lida = false): DatabaseNotification
    {
        $notificacao = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => \Filament\Notifications\DatabaseNotification::class,
            'data' => array_merge([
                'title' => 'Inventário 1/2099 em andamento',
                'body' => '<p>Setor Fictício: até 01/01/2099<br>Segunda linha &amp; mais</p><p>Outro parágrafo</p>',
                'icon' => 'heroicon-o-clipboard-document-check',
                'actions' => [],
                'viewData' => ['chave' => 'teste:'.Str::uuid(), 'tipo' => 'inventario_aberto', 'rota_app' => '/tutorial'],
                'format' => 'filament',
            ], $dados),
        ]);

        if ($lida) {
            $notificacao->markAsRead();
        }

        return $notificacao->refresh();
    }

    public function test_exige_sessao(): void
    {
        $this->getJson('/mobile-api/notificacoes')->assertUnauthorized();
        $this->postJson('/mobile-api/notificacoes/lidas')->assertUnauthorized();
    }

    public function test_lista_so_as_do_usuario(): void
    {
        [$a, $b] = $this->usuarios(2);
        [, $plain] = $this->sessaoPara($a);

        $minha = $this->criar($a);
        $this->criar($b);
        $semView = $this->criar($a, ['viewData' => null, 'body' => null, 'title' => null]);

        $resposta = $this->withToken($plain)->getJson('/mobile-api/notificacoes')->assertOk();

        $ids = collect($resposta->json('notificacoes'))->pluck('id');
        $this->assertTrue($ids->contains($minha->id));
        $this->assertTrue($ids->contains($semView->id));
        $this->assertSame(
            $a->notifications()->count(),
            $resposta->json('meta.total'),
        );

        $item = collect($resposta->json('notificacoes'))->firstWhere('id', $minha->id);
        $this->assertSame('inventario_aberto', $item['tipo']);
        $this->assertSame('/tutorial', $item['rota_app']);
        $this->assertFalse($item['lida']);
        $this->assertSame("Setor Fictício: até 01/01/2099\nSegunda linha & mais\n\nOutro parágrafo", $item['corpo']);

        $vazio = collect($resposta->json('notificacoes'))->firstWhere('id', $semView->id);
        $this->assertSame('', $vazio['titulo']);
        $this->assertSame('', $vazio['corpo']);
        $this->assertNull($vazio['tipo']);
        $this->assertNull($vazio['rota_app']);
        $this->assertSame(20, $resposta->json('meta.per_page'));
    }

    public function test_marca_uma_como_lida(): void
    {
        [$a] = $this->usuarios(1);
        [, $plain] = $this->sessaoPara($a);

        $n = $this->criar($a);
        $antes = $this->withToken($plain)->getJson('/mobile-api/notificacoes')->json('meta.nao_lidas');

        $this->withToken($plain)->postJson("/mobile-api/notificacoes/{$n->id}/lida")->assertNoContent();
        $this->withToken($plain)->postJson("/mobile-api/notificacoes/{$n->id}/lida")->assertNoContent();

        $depois = $this->withToken($plain)->getJson('/mobile-api/notificacoes')->json('meta.nao_lidas');
        $this->assertSame($antes - 1, $depois);
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_nao_marca_de_outro_usuario_404(): void
    {
        [$a, $b] = $this->usuarios(2);
        [, $plain] = $this->sessaoPara($a);

        $dele = $this->criar($b);

        $this->withToken($plain)->postJson("/mobile-api/notificacoes/{$dele->id}/lida")->assertNotFound();
        $this->assertNull($dele->fresh()->read_at);
    }

    public function test_marca_todas(): void
    {
        [$a, $b] = $this->usuarios(2);
        [, $plain] = $this->sessaoPara($a);

        $this->criar($a);
        $this->criar($a);
        $dele = $this->criar($b);

        $this->withToken($plain)->postJson('/mobile-api/notificacoes/lidas')->assertNoContent();

        $this->assertSame(0, $a->unreadNotifications()->count());
        $this->assertNull($dele->fresh()->read_at);
        $this->withToken($plain)->getJson('/mobile-api/notificacoes')->assertJsonPath('meta.nao_lidas', 0);
    }
}
