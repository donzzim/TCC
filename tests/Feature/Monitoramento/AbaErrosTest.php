<?php

namespace Tests\Feature\Monitoramento;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\BuscaOcorrenciaWidget;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\ExcecoesAgrupadasTable;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\LogTable;
use App\Models\egap\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class AbaErrosTest extends TestCase
{
    use CriaUsuariosEgap;
    use DatabaseTransactions;
    use UsaBancoDeMetricas;

    protected array $connectionsToTransact = ['emes'];

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usarBancoDeMetricas();
        Filament::setCurrentPanel(Filament::getPanel('egap'));

        $this->dir = sys_get_temp_dir().'/mon_logs_'.uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir.'/laravel.log',
            "[2026-11-05 10:00:00] local.INFO: usuario entrou\n"
            ."[2026-11-05 10:05:00] local.ERROR: Erro 500 exibido ao usuário {\"ocorrencia\":\"A1B2C3D4\",\"exception\":\"RuntimeException\",\"message\":\"cpf 123.456.789-09 inválido\",\"file\":\"/app/X.php\",\"line\":12} []\n"
            ."#0 /app/Y.php(3): foo()\n"
        );
        config(['monitoramento.logs_dir' => $this->dir]);

        $u = $this->usuarioEgap(User::ROLE_ADMIN);
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);
        $this->actingAs($u, User::GUARD);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);

        parent::tearDown();
    }

    public function test_busca_encontra_ocorrencia_sem_vazar_cpf(): void
    {
        Livewire::test(BuscaOcorrenciaWidget::class, ['periodo' => '1h', 'contexto' => null])
            ->set('codigo', 'a1b2c3d4')
            ->call('buscar')
            ->assertSee('RuntimeException')
            ->assertSee('/app/X.php')
            ->assertDontSee('123.456.789-09');
    }

    public function test_busca_nao_encontrada(): void
    {
        Livewire::test(BuscaOcorrenciaWidget::class, ['periodo' => '1h', 'contexto' => null])
            ->set('codigo', 'FFFFFFFF')
            ->call('buscar')
            ->assertSee('não encontrada');
    }

    public function test_aba_erros_renderiza_tabelas(): void
    {
        Livewire::test(ExcecoesAgrupadasTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSee('RuntimeException')
            ->assertSee('/app/X.php:12');

        Livewire::test(LogTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSee('usuario entrou')
            ->assertSee('Erro 500 exibido ao usuário');
    }
}
