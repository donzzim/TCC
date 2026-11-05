<?php

namespace Tests\Feature\Monitoramento;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\LivewireCarosTable;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\RequisicoesLentasTable;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\RotasLentasTable;
use App\Models\egap\User;
use App\Monitoramento\Agregacao\AgregadoMinuto;
use App\Monitoramento\Agregacao\Histograma;
use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Monitoramento\Concerns\CriaUsuariosEgap;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class AbaDesempenhoTest extends TestCase
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

        $u = $this->usuarioEgap(User::ROLE_ADMIN);
        config(['monitoramento.ativo' => true, 'monitoramento.devs' => [strtolower($u->email)]]);
        $this->actingAs($u, User::GUARD);
    }

    private function agregado(string $rota, int $qtd, float $ms, int $nMaisUm = 0): AgregadoMinuto
    {
        $faixas = Histograma::vazio();
        $faixas[Histograma::faixaDe($ms)] = $qtd;

        return new AgregadoMinuto(intdiv(time(), 60) * 60 - 120, 'egap', $rota, $qtd, 0, 0, $ms * $qtd, $ms, 0, $qtd * 2, 20, $nMaisUm, $faixas);
    }

    public function test_rotas_ordenadas_por_tempo_total(): void
    {
        app(RepositorioDeMetricas::class)->gravarAgregados([
            $this->agregado('rota.poucas.lentas', 3, 4000),
            $this->agregado('rota.muitas.rapidas', 1000, 40, nMaisUm: 2),
        ]);

        Livewire::test(RotasLentasTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSeeInOrder(['rota.muitas.rapidas', 'rota.poucas.lentas'])
            ->assertSee('N+1');
    }

    public function test_ordenar_por_p95(): void
    {
        app(RepositorioDeMetricas::class)->gravarAgregados([
            $this->agregado('rota.poucas.lentas', 3, 4000),
            $this->agregado('rota.muitas.rapidas', 1000, 40),
        ]);

        Livewire::test(RotasLentasTable::class, ['periodo' => '1h', 'contexto' => null])
            ->set('tableFilters.ordem.value', 'p95')
            ->assertSeeInOrder(['rota.poucas.lentas', 'rota.muitas.rapidas']);
    }

    public function test_livewire_so_mostra_componentes(): void
    {
        app(RepositorioDeMetricas::class)->gravarAgregados([
            $this->agregado('rota.comum', 10, 100),
            $this->agregado('livewire:app.tabela-de-bens', 10, 300),
        ]);

        Livewire::test(LivewireCarosTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertSee('app.tabela-de-bens')
            ->assertDontSee('rota.comum');
    }

    public function test_detalhe_mostra_sql_normalizada(): void
    {
        app(RepositorioDeMetricas::class)->gravarDetalhadas([
            new RegistroDeRequisicao(time() - 30, 'egap', 'rota.lenta', 'GET', 200, 2300, 1500, 150, 40, [], true, 'req-abc-123', [
                'top_consultas' => [['connection' => 'emes', 'SQL' => 'select * from egap_bens where setor = ?', 'count' => 120, 'time_ms' => 1200.5]],
                'erro' => null,
            ]),
        ]);

        Livewire::test(RequisicoesLentasTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertSee('rota.lenta')
            ->assertSee('req-abc-123')
            ->mountAction(TestAction::make('detalhes')->table('req-abc-123'))
            ->assertMountedActionModalSee('select * from egap_bens where setor = ?')
            ->assertMountedActionModalSee('120');
    }

    public function test_tabela_vazia_mostra_estado_vazio(): void
    {
        Livewire::test(RequisicoesLentasTable::class, ['periodo' => '1h', 'contexto' => null])
            ->assertOk()
            ->assertSee('Nenhuma requisição lenta');
    }
}
