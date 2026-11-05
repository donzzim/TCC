<?php

namespace Tests\Feature\Monitoramento;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\AgendadorTable;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\FilaWidget;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\JobsFalhosTable;
use App\Models\egap\User;
use App\Monitoramento\Fontes\JobsFalhos;
use App\Monitoramento\Fontes\TarefasAgendadas;
use App\Services\QueueService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class AbaFilasTest extends TestCase
{
    use CriaUsuariosEgap;
    use DatabaseTransactions;
    use UsaBancoDeMetricas;

    protected array $connectionsToTransact = ['emes'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->usarBancoDeMetricas();
        Filament::setCurrentPanel(Filament::getPanel('egap'));
        $this->app->instance(QueueService::class, new class extends QueueService
        {
            public function status(): array
            {
                return ['enabled' => true, 'running' => true, 'pid' => 42];
            }
        });

        $u = $this->usuarioEgap(User::ROLE_ADMIN);
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);
        $this->actingAs($u, User::GUARD);
    }

    private function jobFalho(): string
    {
        $uuid = (string) Str::uuid();

        DB::connection('emes')->table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\EnviarAvisoDeTeste']),
            'exception' => "RuntimeException: SMTP recusou a conexão em 10.0.0.1\n#0 /var/www/app/Jobs/X.php(10)",
            'failed_at' => now()->addMinute(),
        ]);

        return $uuid;
    }

    public function test_lista_job_falho_com_nome_e_primeira_linha(): void
    {
        $uuid = $this->jobFalho();

        $job = JobsFalhos::recentes(5)[0];

        $this->assertSame($uuid, $job['uuid']);
        $this->assertSame('App\\Jobs\\EnviarAvisoDeTeste', $job['job']);
        $this->assertSame('RuntimeException: SMTP recusou a conexão em 10.0.0.1', $job['excecao']);
    }

    public function test_descartar_remove_o_job_falho(): void
    {
        $uuid = $this->jobFalho();

        Livewire::test(JobsFalhosTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertSee('EnviarAvisoDeTeste')
            ->callAction(TestAction::make('descartar')->table($uuid));

        $this->assertFalse(DB::connection('emes')->table('failed_jobs')->where('uuid', $uuid)->exists());
    }

    public function test_agendador_lista_lembretes_do_inventario(): void
    {
        $nomes = array_column(TarefasAgendadas::listar([]), 'tarefa');

        $this->assertContains('egap:inventario-lembretes', $nomes);
        $this->assertContains('monitoramento:coletar', $nomes);
    }

    public function test_tarefa_atrasada(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 10:30:00'));

        $tarefas = collect(TarefasAgendadas::listar([
            'monitoramento:coletar' => ['ultima_execucao' => now()->subMinutes(10)->getTimestamp(), 'sucesso' => true, 'mensagem' => null],
            'egap:inventario-lembretes' => ['ultima_execucao' => Carbon::parse('2026-11-05 07:00:05')->getTimestamp(), 'sucesso' => true, 'mensagem' => null],
        ]))->keyBy('tarefa');

        $this->assertSame('atrasada', $tarefas['monitoramento:coletar']['situacao']);
        $this->assertSame('ok', $tarefas['egap:inventario-lembretes']['situacao']);

        Carbon::setTestNow();
    }

    public function test_tarefa_que_falhou_e_sem_registro(): void
    {
        $tarefas = collect(TarefasAgendadas::listar([
            'monitoramento:coletar' => ['ultima_execucao' => now()->getTimestamp(), 'sucesso' => false, 'mensagem' => 'quebrou'],
        ]))->keyBy('tarefa');

        $this->assertSame('falhou', $tarefas['monitoramento:coletar']['situacao']);
        $this->assertSame('sem registro', $tarefas['egap:inventario-lembretes']['situacao']);
    }

    public function test_widgets_renderizam(): void
    {
        Livewire::test(FilaWidget::class, ['periodo' => '1h', 'contexto' => null])->assertOk()->assertSee('worker ativo');
        Livewire::test(AgendadorTable::class, ['periodo' => '1h', 'contexto' => null])->assertOk()->assertSee('egap:inventario-lembretes');
    }
}
