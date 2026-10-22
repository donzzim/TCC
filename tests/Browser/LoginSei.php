<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginSei extends DuskTestCase
{
    /**
     * A Dusk test example.
     */
    public function testLoginSei(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://sistemas.tjes.jus.br/sip/login.php?sigla_orgao_sistema=TJES&sigla_sistema=SEI&infra_url=L3NlaS8=')
                    ->assertSee('Sistema Eletrônico de Informações');
        });
    }
}
