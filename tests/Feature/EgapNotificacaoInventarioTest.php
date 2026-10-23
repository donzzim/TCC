<?php

namespace Tests\Feature;

use App\Helper\CpfHelper;
use App\Jobs\Egap\NotificarAberturaInventario;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use App\Services\Egap\Inventario\DestinatariosInventario;
use App\Services\Egap\Inventario\NotificadorInventario;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Aviso de abertura do inventário: destinatários por lotação, texto, envio único e disparo.
 */
class EgapNotificacaoInventarioTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private function novoInventario(array $extra = []): Inventario
    {
        return Inventario::query()->create($extra + [
            'num_inventario' => 99,
            'ano_inventario' => 2026,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ]);
    }

    /** @return array<int, Setores> setores específicos (não unidades), para não cobrir árvores inteiras */
    private function setores(int $quantos): array
    {
        $setores = Setores::query()->setoresInventariaveis()->orderBy('id')->take($quantos)->get()->all();

        if (count($setores) < $quantos) {
            $this->markTestSkipped('setores insuficientes neste banco');
        }

        return $setores;
    }

    private function cpfFicticio(): string
    {
        return '999'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    private function usuarioFicticio(array $setorIds = [], array $extra = []): User
    {
        $sufixo = Str::lower(Str::random(10));

        $user = User::query()->create($extra + [
            'login' => "teste.{$sufixo}",
            'name' => "Usuário Teste {$sufixo}",
            'email' => "teste.{$sufixo}@example.invalid",
            'password' => bcrypt(Str::random(16)),
            'cpf' => $this->cpfFicticio(),
            'ativo' => 1,
        ]);

        foreach ($setorIds as $i => $setorId) {
            UserLotacao::query()->create([
                'user_id' => $user->id,
                'setor_id' => $setorId,
                'atualizado_por' => $user->id,
                'principal' => $i === 0,
            ]);
        }

        return $user;
    }

    private function cobrir(Inventario $inventario, Setores $setor, ?string $termino = null): void
    {
        $inventario->unidadesInventariadas()->create([
            'unidades' => $setor->id,
            'data_termino' => $termino,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ]);
    }

    private function notificacoesDe(User $user)
    {
        return $user->notifications()->get()->filter(
            fn ($n): bool => str_starts_with((string) ($n->data['viewData']['chave'] ?? ''), 'inventario_aberto:')
        );
    }

    public function test_destinatarios_por_lotacao(): void
    {
        [$coberto, $outro] = $this->setores(2);
        $dentro = $this->usuarioFicticio([$coberto->id]);
        $fora = $this->usuarioFicticio([$outro->id]);
        $inativo = $this->usuarioFicticio([$coberto->id], ['ativo' => 0]);

        $destinatarios = DestinatariosInventario::porSetor(collect([(int) $coberto->id]));

        $this->assertSame([(int) $coberto->id], $destinatarios[$dentro->id]);
        $this->assertFalse($destinatarios->has($fora->id));
        $this->assertFalse($destinatarios->has($inativo->id));
    }

    public function test_destinatarios_pela_lotacao_legada_quando_sem_user_lotacao(): void
    {
        [$antigo, $atual] = $this->setores(2);
        $user = $this->usuarioFicticio();
        $comLotacaoNova = $this->usuarioFicticio([$antigo->id]);

        $egap = UserEgap::query()->create(['name' => 'Teste Legado', 'username' => 'legado.'.Str::random(8), 'params' => '']);
        InfoUser::query()->create(['usuario_id' => $egap->id, 'cpf' => CpfHelper::format($user->cpf)]);
        $velha = Lotacao::query()->create(['id_user' => $egap->id, 'setor' => $antigo->id]);
        $velha->forceFill(['date_time' => now()->subYear()])->saveQuietly();
        Lotacao::query()->create(['id_user' => $egap->id, 'setor' => $atual->id]);

        $porAtual = DestinatariosInventario::porSetor(collect([(int) $atual->id]));
        $porAntigo = DestinatariosInventario::porSetor(collect([(int) $antigo->id]));

        // só vale a lotação legada mais recente
        $this->assertSame([(int) $atual->id], $porAtual[$user->id]);
        $this->assertFalse($porAntigo->has($user->id));
        $this->assertTrue($porAntigo->has($comLotacaoNova->id));
    }

    public function test_abertura_um_aviso_por_usuario_com_prazos(): void
    {
        [$a, $b] = $this->setores(2);
        $inventario = $this->novoInventario(['num_inventario' => 77, 'ano_inventario' => 2026]);
        $this->cobrir($inventario, $a, '2026-11-20 00:00:00');
        $this->cobrir($inventario, $b, '2026-11-10 00:00:00');
        $user = $this->usuarioFicticio([$a->id, $b->id]);

        $this->assertGreaterThanOrEqual(1, NotificadorInventario::notificarAbertura($inventario));

        $notificacoes = $this->notificacoesDe($user);
        $this->assertCount(1, $notificacoes);

        $data = $notificacoes->first()->data;
        $this->assertSame('Inventário 77/2026 em andamento — Prazo: 10/11/2026', $data['title']);
        $this->assertStringContainsString(e($a->setor).': até 20/11/2026', $data['body']);
        $this->assertStringContainsString(e($b->setor).': até 10/11/2026', $data['body']);
        $this->assertStringContainsString('Como fazer a conferência:', $data['body']);
        $this->assertSame('inventario_aberto:'.$inventario->id, $data['viewData']['chave']);
        $this->assertSame('inventario_aberto', $data['viewData']['tipo']);
        $this->assertSame('/tutorial', $data['viewData']['rota_app']);
        $this->assertSame('Ir para a conferência', $data['actions'][0]['label']);
        $this->assertStringEndsWith('/egap/externo/patrimonio/inventario', $data['actions'][0]['url']);
    }

    public function test_abertura_sem_prazo_definido(): void
    {
        [$a] = $this->setores(1);
        $inventario = $this->novoInventario(['num_inventario' => 78]);
        $this->cobrir($inventario, $a);
        $user = $this->usuarioFicticio([$a->id]);

        NotificadorInventario::notificarAbertura($inventario);

        $data = $this->notificacoesDe($user)->first()->data;
        $this->assertSame('Inventário 78/2026 em andamento — Prazo: não definido', $data['title']);
        $this->assertStringContainsString(e($a->setor).': sem prazo definido', $data['body']);
    }

    public function test_abertura_nao_repete(): void
    {
        [$a] = $this->setores(1);
        $inventario = $this->novoInventario();
        $this->cobrir($inventario, $a, '2026-11-10 00:00:00');
        $user = $this->usuarioFicticio([$a->id]);

        $this->assertGreaterThanOrEqual(1, NotificadorInventario::notificarAbertura($inventario));
        $this->assertSame(0, NotificadorInventario::notificarAbertura($inventario));
        $this->assertCount(1, $this->notificacoesDe($user));
    }

    public function test_mudar_para_em_execucao_dispara_job(): void
    {
        Bus::fake();
        $inventario = $this->novoInventario();

        $inventario->update(['dias' => 5]);
        Bus::assertNotDispatched(NotificarAberturaInventario::class);

        $inventario->update(['situacao' => Inventario::SITUACAO_EM_EXECUCAO]);
        Bus::assertDispatched(NotificarAberturaInventario::class, fn ($job): bool => $job->inventarioId === (int) $inventario->id);

        Bus::fake();
        $inventario->update(['dias' => 6]);
        Bus::assertNotDispatched(NotificarAberturaInventario::class);
    }

    public function test_criar_ja_em_execucao_dispara_job(): void
    {
        Bus::fake();
        $this->novoInventario(['situacao' => Inventario::SITUACAO_EM_EXECUCAO]);

        Bus::assertDispatched(NotificarAberturaInventario::class);
    }

    public function test_aparece_no_sino_do_painel(): void
    {
        [$a] = $this->setores(1);
        $inventario = $this->novoInventario();
        $this->cobrir($inventario, $a, '2026-11-10 00:00:00');
        $user = $this->usuarioFicticio([$a->id]);

        NotificadorInventario::notificarAbertura($inventario);

        $this->assertSame(1, $user->unreadNotifications()->count());
        $notificacao = $user->unreadNotifications()->first();
        $this->assertSame(User::class, $notificacao->notifiable_type);

        // o sino renderiza o corpo como HTML sanitizado: parágrafos e quebras precisam sobreviver
        $html = Notification::fromDatabase($notificacao)->toEmbeddedHtml();
        $this->assertStringContainsString('Como fazer a conferência:', $html);
        $this->assertMatchesRegularExpression('/Como fazer a conferência:\s*<br\s*\/?>\s*1\./u', $html);
    }
}
