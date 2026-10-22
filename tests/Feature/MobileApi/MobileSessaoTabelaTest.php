<?php

namespace Tests\Feature\MobileApi;

use App\Models\egap\MobileSessao;
use App\Models\egap\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tabela `egap_mobile_sessoes`: uma linha por token Sanctum do app mobile,
 * removida em cascata quando o token é apagado.
 */
class MobileSessaoTabelaTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    public function test_sessao_some_quando_o_token_e_apagado(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('Banco sem usuários.');
        }

        $t = $user->createToken('mobile-app');
        $tokenId = $t->accessToken->id;

        MobileSessao::create([
            'token_id' => $tokenId,
            'user_id' => $user->id,
            'info' => ['nome' => 'x'],
        ]);

        $this->assertDatabaseHas('egap_mobile_sessoes', ['token_id' => $tokenId], 'emes');
        $this->assertSame(['nome' => 'x'], MobileSessao::where('token_id', $tokenId)->first()->info);

        $t->accessToken->delete();

        $this->assertDatabaseMissing('egap_mobile_sessoes', ['token_id' => $tokenId], 'emes');
    }

    public function test_tabela_tem_colunas_do_bloco(): void
    {
        $this->assertTrue(Schema::connection('emes')->hasColumns('egap_mobile_sessoes', [
            'token_id', 'user_id', 'egap_id', 'setor_id', 'codigodauo', 'info', 'dispositivo',
        ]));
    }
}
