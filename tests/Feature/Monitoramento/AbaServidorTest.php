<?php

namespace Tests\Feature\Monitoramento;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\ServidorWidget;
use App\Models\egap\User;
use App\Monitoramento\Fontes\ChecklistDeConfiguracao;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class AbaServidorTest extends TestCase
{
    use CriaUsuariosEgap;
    use DatabaseTransactions;
    use UsaBancoDeMetricas;

    protected array $connectionsToTransact = ['emes'];

    private function item(string $titulo): array
    {
        return collect(ChecklistDeConfiguracao::avaliar())->firstWhere('titulo', $titulo);
    }

    public function test_itens_na_ordem(): void
    {
        $this->assertSame([
            'APP_DEBUG desligado fora de local',
            'Ambiente',
            'Configuração em cache',
            'Rotas em cache',
            'Debugbar desativado',
            'Fila assíncrona',
            'OPcache ativo',
            'Log com rotação',
        ], array_column(ChecklistDeConfiguracao::avaliar(), 'titulo'));
    }

    public function test_debug_em_producao_falha_o_item(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);
        $this->assertFalse($this->item('APP_DEBUG desligado fora de local')['ok']);

        $this->app['env'] = 'local';
        $this->assertTrue($this->item('APP_DEBUG desligado fora de local')['ok']);
        $this->assertSame('APP_DEBUG=true só no ambiente local', $this->item('APP_DEBUG desligado fora de local')['detalhe']);
    }

    public function test_fila_sync_falha_o_item(): void
    {
        config(['queue.default' => 'sync']);
        $this->assertFalse($this->item('Fila assíncrona')['ok']);

        config(['queue.default' => 'database']);
        $this->assertTrue($this->item('Fila assíncrona')['ok']);
    }

    public function test_log_com_rotacao(): void
    {
        config(['logging.default' => 'stack', 'logging.channels.stack.channels' => ['single']]);
        $this->assertFalse($this->item('Log com rotação')['ok']);

        config(['logging.channels.stack.channels' => ['daily']]);
        $this->assertTrue($this->item('Log com rotação')['ok']);
    }

    public function test_widget_mostra_versao_do_php(): void
    {
        $this->usarBancoDeMetricas();
        Filament::setCurrentPanel(Filament::getPanel('egap'));
        $u = $this->usuarioEgap(User::ROLE_ADMIN);
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);
        $this->actingAs($u, User::GUARD);

        Livewire::test(ServidorWidget::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSee(PHP_VERSION)
            ->assertSee('Fila assíncrona');
    }
}
