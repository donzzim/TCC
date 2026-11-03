<?php

namespace Tests\Feature;

use App\Filament\Egap\Externo\Pages\HomePageExterno;
use App\Filament\Egap\Pages\EgapDashboard;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\PatrimonioOverviewStats;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Almoxarifado\RequisicoesBensConsumoAtendidas;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\BensPatrimoniaisBaixados;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\SeletorGraficosWidget;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\TransparenciaAlmoxarifadoWidget;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\TransparenciaPatrimonioWidget;
use App\Models\egap\User;
use Database\Seeders\Spatie\EgapRolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Os gráficos de Patrimônio e Almoxarifado saíram da página "Portal Transparência" do
 * ambiente externo e viraram widgets do dashboard principal, logo depois dos números gerais.
 */
class EgapDashboardTransparenciaTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EgapRolesPermissionsSeeder::class);
        Filament::setCurrentPanel('egap');

        $user = User::query()->where('ativo', 1)->orderBy('id')->get()->first(fn (User $u): bool => ! $u->isAdminEgap());
        $user->syncRoles([User::ROLE_USER, User::ROLE_EXTERNO]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user->fresh(), User::GUARD);
    }

    public function test_graficos_aparecem_logo_depois_dos_numeros_gerais(): void
    {
        $ordenados = collect((new EgapDashboard)->getWidgets())
            ->sortBy(fn (string $widget): int => $widget::getSort())
            ->values();

        $this->assertSame(
            [PatrimonioOverviewStats::class, TransparenciaPatrimonioWidget::class, TransparenciaAlmoxarifadoWidget::class],
            $ordenados->take(3)->all(),
        );

        $this->get(EgapDashboard::getUrl(panel: 'egap'))
            ->assertOk()
            ->assertSee('Seção de Patrimônio')
            ->assertSee('Seção de Almoxarifado');
    }

    public function test_cartao_de_patrimonio_troca_indicador_e_tipo(): void
    {
        Livewire::test(TransparenciaPatrimonioWidget::class)
            ->assertOk()
            ->assertSet('selectedIndicator', 'bens_adquiridos')
            ->set('selectedIndicator', 'bens_baixados')
            ->set('chartType', 'pie')
            ->call('generateChart')
            ->assertSet('currentWidget', BensPatrimoniaisBaixados::class)
            ->assertSet('chartType', 'pie');
    }

    public function test_cartao_de_almoxarifado_troca_indicador_e_recusa_tipo_invalido(): void
    {
        Livewire::test(TransparenciaAlmoxarifadoWidget::class)
            ->assertOk()
            ->set('selectedIndicator', 'requisicoes')
            ->set('chartType', 'invalido')
            ->call('generateChart')
            ->assertSet('currentWidget', RequisicoesBensConsumoAtendidas::class)
            ->assertSet('chartType', 'bar');
    }

    public function test_todos_os_graficos_movidos_renderizam(): void
    {
        foreach ([TransparenciaPatrimonioWidget::class, TransparenciaAlmoxarifadoWidget::class] as $cartao) {
            $graficos = (new ReflectionMethod($cartao, 'graficos'))->invoke(new $cartao);

            foreach ($graficos as $grafico) {
                Livewire::test($grafico, ['chartType' => 'bar'])->assertOk();
            }
        }

        $this->assertTrue(is_subclass_of(TransparenciaPatrimonioWidget::class, SeletorGraficosWidget::class));
    }

    public function test_portal_transparencia_saiu_do_ambiente_externo(): void
    {
        $this->assertFalse(class_exists('App\\Filament\\Egap\\Externo\\Pages\\PortalTransparencia'));

        $this->get(HomePageExterno::getUrl(panel: 'egap'))
            ->assertOk()
            ->assertDontSee('Portal Transparência');
    }
}
