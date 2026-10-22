<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Facebook\WebDriver\WebDriverKeys as Keys;

class SeiLoginAndSearchTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     */
    public function testLogin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://sistemas.tjes.jus.br/sip/login.php?sigla_orgao_sistema=TJES&sigla_sistema=SEI&infra_url=L3NlaS8=')

                ->assertSee('Sistema Eletrônico de Informações')
                ->type('#txtUsuario', env('SEI_USER'))
                ->type('#pwdSenha', env('SEI_PASSWORD'))
                ->click('#sbmAcessar')

                ->waitForText('Pesquisa Rápida', 10)

                ->type('#txtPesquisaRapida', '7003561-74.2024.8.08.0000')
                ->keys('input[name="txtPesquisaRapida"]', Keys::ENTER)

                ->pause(2000);
        });
    }
    public function testCriarProcess(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://sistemas.tjes.jus.br/sei/controlador.php?acao=procedimento_escolher_tipo&infra_item_menu=18&infra_sistema=100000100&infra_unidade_atual=1029&infra_hash=a91f3a5b3aa4402e0277442f20316fc050be2621ee0a3fc9c1bc083ad549d845')

                ->assertSee('Iniciar Processo')
                ->click('#ancExibirTiposProcedimento')

                ->waitForText('Aquisição de Material e Contratação de Serviço: Dispensa e Inexigibilidade de Licitação', 10)

                ->pause(2000);
        });
    }
}
