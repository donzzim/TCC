<?php

namespace Tests\Feature\Monitoramento;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstalarMonitoramentoTest extends TestCase
{
    public function test_instalar_cria_tabelas_somente_no_banco_de_metricas(): void
    {
        $arquivo = sys_get_temp_dir().'/mon_'.uniqid().'.sqlite';
        config(['database.connections.monitoramento.database' => $arquivo]);
        DB::purge('monitoramento');

        $this->artisan('monitoramento:instalar')->assertSuccessful();

        $this->assertFileExists($arquivo);
        foreach (['agregados_minuto', 'agregados_hora', 'requisicoes', 'snapshots', 'batimentos'] as $tabela) {
            $this->assertTrue(Schema::connection('monitoramento')->hasTable($tabela), $tabela);
            $this->assertFalse(Schema::connection('emes')->hasTable($tabela), $tabela);
        }

        DB::purge('monitoramento');
        @unlink($arquivo);
    }

    public function test_devs_e_normalizado(): void
    {
        putenv('MONITORAMENTO_DEVS= Fulano@TJES.jus.br,, outro@tjes.jus.br ');
        $this->refreshApplication();

        $this->assertSame(['fulano@tjes.jus.br', 'outro@tjes.jus.br'], config('monitoramento.devs'));

        putenv('MONITORAMENTO_DEVS');
    }

    public function test_padroes_da_configuracao(): void
    {
        $this->assertFalse(config('monitoramento.ativo'));
        $this->assertSame(1000, config('monitoramento.limiar_lenta_ms'));
        $this->assertSame(20, config('monitoramento.limiar_n_mais_um'));
        $this->assertCount(13, config('monitoramento.faixas_ms'));
        $this->assertSame('sqlite', config('database.connections.monitoramento.driver'));
    }
}
