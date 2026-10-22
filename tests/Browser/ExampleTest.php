<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Facebook\WebDriver\WebDriverKeys as Keys;

class ExampleTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     */
    public function testBasicExample(): void
    {
        // $this->browse(function (Browser $browser) {
        //     $browser->visit('https://sistemas.tjes.jus.br/sip/login.php?sigla_orgao_sistema=TJES&sigla_sistema=SEI&infra_url=L3NlaS8=')
        //         ->assertSee('Sistema Eletrônico de Informações')
        //         ->type('#txtUsuario', env('SEI_USER'))
        //         ->type('#pwdSenha', env('SEI_USER'))
        //         ->click('#sbmAcessar')
        //         ->pause(1000)
        //         ->type('#txtPesquisaRapida', '7003561-74.2024.8.08.0000')
        //         ->keys('input[name="txtPesquisaRapida"]', Keys::ENTER)
        //         ->pause(1000);
        // });

        // $this->browse(function (Browser $browser) {
        //     $browser->visit('https://sistemas.tjes.jus.br/Siplag4/faces/login.jsp')
        //         ->assertSee('Sistema Integrado de Gestão das Finanças Públicas do Espírito Santo')
        //         ->type('#loginBox:itxUsuario::content', env('SIGEFES_USER'))
        //         ->type('#loginBox:itxSenhaAtual::content', env('SIGEFES_USER'))
        //         ->click('#loginBox:btnConfirmar')
        //         ->pause(1000);
        // });
    }
}
