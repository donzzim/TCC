<?php

namespace Tests\Feature\Monitoramento;

use App\Models\egap\User;
use App\Monitoramento\Fontes\StatusDaFila;
use App\Monitoramento\Fontes\StatusDoMysql;
use App\Monitoramento\Fontes\StatusDoServidor;
use App\Monitoramento\Fontes\UsuariosOnline;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\TestCase;

class FontesDeSaudeTest extends TestCase
{
    use CriaUsuariosEgap;
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes'];

    private function sessao(int $userId, int $ultimaAtividade): array
    {
        return [
            'id' => Str::random(40),
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'teste',
            'payload' => '',
            'last_activity' => $ultimaAtividade,
        ];
    }

    public function test_online_separa_interno_externo_e_app(): void
    {
        $agora = time();
        $interno = $this->usuarioEgap();
        $externo = $this->usuarioEgap(User::ROLE_EXTERNO);
        $antes = UsuariosOnline::contar($agora);

        DB::connection('emes')->table('sessions')->insert([
            $this->sessao($interno->id, $agora - 60),
            $this->sessao($interno->id, $agora - 30),
            $this->sessao($externo->id, $agora - 120),
            $this->sessao($this->usuarioEgap()->id, $agora - 600),
        ]);
        DB::connection('emes')->table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $interno->id,
            'name' => 'teste',
            'token' => hash('sha256', Str::random(40)),
            'abilities' => '["*"]',
            'last_used_at' => now()->subMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $depois = UsuariosOnline::contar($agora);

        $this->assertSame($antes['web_interno'] + 1, $depois['web_interno']);
        $this->assertSame($antes['web_externo'] + 1, $depois['web_externo']);
        $this->assertSame($antes['app'] + 1, $depois['app']);
    }

    public function test_mysql_traz_conexoes(): void
    {
        $status = StatusDoMysql::ler();

        $this->assertIsInt($status['threads_connected']);
        $this->assertGreaterThan(0, $status['max_connections']);
        $this->assertIsFloat($status['long_query_time']);
        $this->assertArrayHasKey('slow_query_log', $status);
    }

    public function test_fila_conta_pendentes_e_falhos(): void
    {
        $this->app->instance(QueueService::class, new class extends QueueService
        {
            public function status(): array
            {
                return ['enabled' => true, 'running' => false, 'pid' => null];
            }
        });
        $agora = time();
        $antes = StatusDaFila::ler($agora);

        DB::connection('emes')->table('jobs')->insert([
            'queue' => 'monitoramento-teste',
            'payload' => '{}',
            'attempts' => 0,
            'available_at' => $agora - 900,
            'created_at' => $agora - 900,
        ]);
        DB::connection('emes')->table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'x',
            'failed_at' => now(),
        ]);

        $depois = StatusDaFila::ler($agora);

        $this->assertSame($antes['pendentes'] + 1, $depois['pendentes']);
        $this->assertSame(1, $depois['por_fila']['monitoramento-teste']);
        $this->assertGreaterThanOrEqual(900, $depois['idade_mais_antigo_seg']);
        $this->assertSame($antes['falhos_total'] + 1, $depois['falhos_total']);
        $this->assertSame($antes['falhos_24h'] + 1, $depois['falhos_24h']);
        $this->assertFalse($depois['worker']['running']);
    }

    public function test_worker_que_falha_vira_null(): void
    {
        $this->app->instance(QueueService::class, new class extends QueueService
        {
            public function status(): array
            {
                throw new RuntimeException('sem acesso');
            }
        });

        $this->assertNull(StatusDaFila::ler(time())['worker']);
    }

    public function test_servidor_traz_versoes(): void
    {
        $servidor = StatusDoServidor::ler();

        $this->assertSame(PHP_VERSION, $servidor['php']);
        $this->assertNotEmpty($servidor['laravel']);
        $this->assertNotEmpty($servidor['filament']);
        $this->assertNotNull($servidor['mysql']);
        $this->assertGreaterThan(0, $servidor['disco_total_bytes']);
        $this->assertSame('feat/painel-monitoramento', $servidor['branch']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $servidor['commit']);
    }
}
