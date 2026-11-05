<?php

namespace Tests\Feature\Monitoramento;

use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Coleta\Snapshot;
use App\Services\QueueService;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class SnapshotTest extends TestCase
{
    use UsaBancoDeMetricas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usarBancoDeMetricas();
        config([
            'monitoramento.ativo' => true,
            'monitoramento.buffer_dir' => sys_get_temp_dir().'/mon_snap_'.uniqid().'/buffer',
        ]);
        $this->app->forgetInstance(BufferDeRequisicoes::class);
        $this->app->instance(QueueService::class, new class extends QueueService
        {
            public function status(): array
            {
                return ['enabled' => true, 'running' => true, 'pid' => 1];
            }
        });
    }

    private function tarefa(string $comando)
    {
        return app(Schedule::class)->command($comando);
    }

    public function test_snapshot_ida_e_volta(): void
    {
        $s = new Snapshot(10, ['web_interno' => 1], null, ['pendentes' => 0], null);

        $this->assertEquals($s, Snapshot::fromArray($s->toArray()));
    }

    public function test_coletar_grava_snapshot(): void
    {
        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $linhas = DB::connection('monitoramento')->table('snapshots')->get();
        $this->assertCount(1, $linhas);

        $snapshot = Snapshot::fromArray(['instante' => (int) $linhas[0]->instante, ...json_decode($linhas[0]->dados, true)]);
        $this->assertIsArray($snapshot->online);
        $this->assertIsInt($snapshot->mysql['threads_connected']);
        $this->assertTrue($snapshot->fila['worker']['running']);
        $this->assertSame(PHP_VERSION, $snapshot->servidor['php']);
    }

    public function test_batimento_de_tarefa_agendada(): void
    {
        event(new ScheduledTaskFinished($this->tarefa('egap:inventario-lembretes'), 0.1));

        $b = DB::connection('monitoramento')->table('batimentos')->where('tarefa', 'egap:inventario-lembretes')->first();
        $this->assertNotNull($b);
        $this->assertSame(1, (int) $b->sucesso);
    }

    public function test_batimento_de_tarefa_que_falhou(): void
    {
        event(new ScheduledTaskFailed($this->tarefa('egap:inventario-lembretes'), new RuntimeException('quebrou')));

        $b = DB::connection('monitoramento')->table('batimentos')->where('tarefa', 'egap:inventario-lembretes')->first();
        $this->assertSame(0, (int) $b->sucesso);
        $this->assertSame('quebrou', $b->mensagem);
    }

    public function test_batimento_desligado_nao_grava(): void
    {
        config(['monitoramento.ativo' => false]);

        event(new ScheduledTaskFinished($this->tarefa('egap:inventario-lembretes'), 0.1));

        $this->assertSame(0, DB::connection('monitoramento')->table('batimentos')->count());
    }
}
