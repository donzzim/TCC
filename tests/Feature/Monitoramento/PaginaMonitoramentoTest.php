<?php

namespace Tests\Feature\Monitoramento;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\ErrosChart;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\IndicadoresGeraisWidget;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\LatenciaChart;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\SemaforoWidget;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\UsuariosOnlineChart;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\VolumePorContextoChart;
use App\Models\egap\User;
use App\Monitoramento\AcessoAoMonitoramento;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class PaginaMonitoramentoTest extends TestCase
{
    use CriaUsuariosEgap;
    use DatabaseTransactions;
    use UsaBancoDeMetricas;

    protected array $connectionsToTransact = ['emes'];

    private const URL = '/egap/admin-egap/monitoramento';

    protected function setUp(): void
    {
        parent::setUp();

        $this->usarBancoDeMetricas();
        Filament::setCurrentPanel(Filament::getPanel('egap'));
    }

    private function dev(): User
    {
        $u = $this->usuarioEgap(User::ROLE_ADMIN);
        $u->forceFill(['email_verified_at' => now()])->save();
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);

        return $u;
    }

    public function test_dev_admin_acessa(): void
    {
        $u = $this->dev();

        $this->actingAs($u, User::GUARD)->get(self::URL)->assertOk()->assertSee('Monitoramento');
    }

    public function test_admin_fora_da_lista_nao_acessa(): void
    {
        $u = $this->dev();
        config(['monitoramento.devs' => ['outro@example.invalid']]);

        $this->actingAs($u, User::GUARD)->get(self::URL)->assertForbidden();
    }

    public function test_dev_que_nao_e_admin_nao_acessa(): void
    {
        $u = $this->usuarioEgap(User::ROLE_USER);
        $u->forceFill(['email_verified_at' => now()])->save();
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);

        $this->assertFalse(AcessoAoMonitoramento::permitido($u));
        $this->actingAs($u, User::GUARD)->get(self::URL)->assertForbidden();
    }

    public function test_desligado_ninguem_acessa(): void
    {
        $u = $this->dev();
        config(['monitoramento.ativo' => false]);

        $this->actingAs($u, User::GUARD)->get(self::URL)->assertForbidden();
    }

    public function test_lista_vazia_ninguem_acessa(): void
    {
        $u = $this->dev();
        config(['monitoramento.devs' => []]);

        $this->assertFalse(AcessoAoMonitoramento::permitido($u));
        $this->assertFalse(AcessoAoMonitoramento::permitido(null));
    }

    public function test_visao_geral_renderiza_sem_dados(): void
    {
        $this->actingAs($this->dev(), User::GUARD);

        Livewire::test(IndicadoresGeraisWidget::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSee('Usuários online')
            ->assertSee('indisponível');

        Livewire::test(SemaforoWidget::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSee('nenhuma coleta registrada');

        foreach ([LatenciaChart::class, VolumePorContextoChart::class, ErrosChart::class, UsuariosOnlineChart::class] as $grafico) {
            Livewire::test($grafico, ['periodo' => '1h', 'contexto' => null])->assertOk();
        }
    }

    public function test_pagina_abre_sem_o_banco_de_metricas(): void
    {
        $u = $this->dev();
        config(['database.connections.monitoramento.database' => sys_get_temp_dir().'/nao/existe/'.uniqid().'.sqlite']);
        \Illuminate\Support\Facades\DB::purge('monitoramento');
        $this->actingAs($u, User::GUARD);

        $this->get(self::URL)->assertOk()->assertSee('Nenhuma coleta registrada');

        foreach ([IndicadoresGeraisWidget::class, SemaforoWidget::class, LatenciaChart::class, VolumePorContextoChart::class, ErrosChart::class, UsuariosOnlineChart::class] as $widget) {
            Livewire::test($widget, ['periodo' => '1h', 'contexto' => null])->assertOk();
        }
    }

    public function test_widget_sem_permissao_e_bloqueado(): void
    {
        $u = $this->dev();
        config(['monitoramento.devs' => []]);
        $this->actingAs($u, User::GUARD);

        Livewire::test(IndicadoresGeraisWidget::class, ['periodo' => '1h', 'contexto' => null])->assertForbidden();
    }
}
