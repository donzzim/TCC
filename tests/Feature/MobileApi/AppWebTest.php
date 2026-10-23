<?php

namespace Tests\Feature\MobileApi;

use Tests\TestCase;

class AppWebTest extends TestCase
{
    public function test_app_web_servido_em_egap_mobile(): void
    {
        $this->get('/egap-mobile')->assertOk();
        $this->get('/egap-mobile/bens')->assertOk();
    }

    public function test_endereco_antigo_redireciona(): void
    {
        $this->get('/inventario')->assertRedirect('/egap-mobile');
        $this->get('/inventario/pedidos/carrinho')->assertRedirect('/egap-mobile/pedidos/carrinho');
    }
}
