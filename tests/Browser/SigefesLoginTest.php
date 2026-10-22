<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SigefesLoginTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     * sail artisan dusk tests/Browser/SigefesLoginTest.php
     */
    public function testLoginOnSigefes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://sistemas.tjes.jus.br/Siplag4/faces/login.jsp')
                ->assertSee('Autenticar com certificado digital')

                ->type('#loginBox:itxUsuario::content', env('SIGEFES_USER'))
                ->typeSlowly('#loginBox:itxSenhaAtual::content', env('SIGEFES_PASSWORD'))
                ->type('#loginBox:cbxExercicio::content', date('Y'))
                ->pause(500)

                ->waitFor('#loginBox:btnConfirmar')
                ->press('Ok')

                ->pause(4000);
        });
    }
}
