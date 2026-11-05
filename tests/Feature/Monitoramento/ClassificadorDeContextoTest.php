<?php

namespace Tests\Feature\Monitoramento;

use App\Monitoramento\Coleta\ClassificadorDeContexto;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClassificadorDeContextoTest extends TestCase
{
    private function livewire(array $componentes): Request
    {
        return Request::create('/livewire/update', 'POST', [
            'components' => array_map(
                fn (array $memo) => ['snapshot' => json_encode(['memo' => $memo])],
                $componentes
            ),
        ]);
    }

    public function test_contextos_por_prefixo(): void
    {
        $casos = [
            'mobile-api/sessao' => 'mobile-api',
            'egap/externo/home' => 'egap-externo',
            'egap/admin-egap/roles' => 'egap',
            'egap' => 'egap',
            'admin/users' => 'admin',
            'emes/admin/users' => 'admin',
            'app/cursos' => 'app',
            'emes/app/x' => 'app',
            'qualquer' => 'outros',
        ];

        foreach ($casos as $path => $esperado) {
            $this->assertSame($esperado, ClassificadorDeContexto::contexto(Request::create('/'.$path)), $path);
        }
    }

    public function test_livewire_usa_o_path_do_componente_e_nomes_na_rota(): void
    {
        $r = $this->livewire([['name' => 'app.filament.x.tabela', 'path' => 'egap/externo/home']]);

        $this->assertSame('egap-externo', ClassificadorDeContexto::contexto($r));
        $this->assertSame('livewire:app.filament.x.tabela', ClassificadorDeContexto::rota($r));
    }

    public function test_rota_sem_rota_registrada(): void
    {
        $this->assertSame('(sem rota)', ClassificadorDeContexto::rota(Request::create('/egap/123/editar')));
    }

    public function test_ignora_o_proprio_painel_e_assets(): void
    {
        $this->assertTrue(ClassificadorDeContexto::deveIgnorar(Request::create('/egap/admin-egap/monitoramento')));
        $this->assertTrue(ClassificadorDeContexto::deveIgnorar(Request::create('/_debugbar/open')));
        $this->assertTrue(ClassificadorDeContexto::deveIgnorar(Request::create('/favicon.ico')));
        $this->assertTrue(ClassificadorDeContexto::deveIgnorar($this->livewire([
            ['name' => 'app.filament.egap.interno.groups.admin.monitoramento.widgets.latencia-chart', 'path' => 'egap/admin-egap/monitoramento'],
        ])));
        $this->assertFalse(ClassificadorDeContexto::deveIgnorar(Request::create('/egap/externo/home')));
        $this->assertFalse(ClassificadorDeContexto::deveIgnorar($this->livewire([['name' => 'app.x', 'path' => 'egap']])));
    }
}
