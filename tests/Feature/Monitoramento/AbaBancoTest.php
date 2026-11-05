<?php

namespace Tests\Feature\Monitoramento;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\ConsultasServidorTable;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\MysqlIndicadoresWidget;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\ProcessosTable;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\TabelasTable;
use App\Models\egap\User;
use App\Monitoramento\Fontes\ConsultasDoServidor;
use App\Monitoramento\Fontes\ProcessosDoMysql;
use App\Monitoramento\Fontes\StatusDoMysql;
use App\Monitoramento\Fontes\TabelasDoBanco;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class AbaBancoTest extends TestCase
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
    }

    private function conexaoQuebrada(): void
    {
        config([
            'database.connections.monitoramento_quebrada' => ['driver' => 'sqlite', 'database' => sys_get_temp_dir().'/nao/existe/'.uniqid().'.sqlite', 'prefix' => ''],
            'monitoramento.conexao_leitura' => 'monitoramento_quebrada',
        ]);
    }

    private function entrarComoDev(): void
    {
        $u = $this->usuarioEgap(User::ROLE_ADMIN);
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);
        $this->actingAs($u, User::GUARD);
    }

    public function test_fonte_que_falha_retorna_null(): void
    {
        $this->conexaoQuebrada();

        $this->assertNull(ProcessosDoMysql::emExecucao());
        $this->assertNull(ConsultasDoServidor::topPorTempo());
        $this->assertNull(TabelasDoBanco::maiores());
        $this->assertNull(TabelasDoBanco::myisam());
        $this->assertNull(StatusDoMysql::ler());
    }

    public function test_tabelas_lista_myisam_do_inventario(): void
    {
        $this->assertContains('egap_inv_atividades', TabelasDoBanco::myisam());

        $maiores = TabelasDoBanco::maiores(5);
        $this->assertCount(5, $maiores);
        $this->assertSame(['tabela', 'motor', 'linhas', 'dados_mb', 'indices_mb'], array_keys($maiores[0]));
    }

    public function test_processos_nao_inclui_a_propria_conexao(): void
    {
        $proprio = (int) \DB::connection('emes')->selectOne('select connection_id() as id')->id;

        $ids = array_column(ProcessosDoMysql::emExecucao(), 'id');

        $this->assertNotContains($proprio, $ids);
    }

    public function test_consultas_do_servidor_tem_formato(): void
    {
        $consultas = ConsultasDoServidor::topPorTempo(3);

        $this->assertIsArray($consultas);
        if ($consultas !== []) {
            $this->assertSame(['sql', 'execucoes', 'tempo_total_ms', 'media_ms', 'linhas_examinadas', 'linhas_retornadas'], array_keys($consultas[0]));
        }
    }

    public function test_widgets_renderizam(): void
    {
        $this->entrarComoDev();

        Livewire::test(MysqlIndicadoresWidget::class, ['periodo' => '1h', 'contexto' => null])->assertOk()->assertSee('Conexões');
        Livewire::test(TabelasTable::class, ['periodo' => '1h', 'contexto' => null])->assertOk()->assertSee('MyISAM');
        Livewire::test(ProcessosTable::class, ['periodo' => '1h', 'contexto' => null])->assertOk();
        Livewire::test(ConsultasServidorTable::class, ['periodo' => '1h', 'contexto' => null])->assertOk();
    }

    public function test_widget_mostra_indisponivel_quando_fonte_falha(): void
    {
        $this->entrarComoDev();
        $this->conexaoQuebrada();

        Livewire::test(MysqlIndicadoresWidget::class, ['periodo' => '1h', 'contexto' => null])->assertSee('indisponível');
        Livewire::test(ProcessosTable::class, ['periodo' => '1h', 'contexto' => null])->assertSee('indisponível');
        Livewire::test(TabelasTable::class, ['periodo' => '1h', 'contexto' => null])->assertSee('indisponível');
    }
}
