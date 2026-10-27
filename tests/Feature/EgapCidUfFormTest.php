<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource\Pages\ListCidUfs;
use App\Models\egap\Patrimonio\BensImoveis\Cidades;
use App\Models\egap\Patrimonio\BensImoveis\CidUf;
use App\Models\egap\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EgapCidUfFormTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::query()->get()->first(fn (User $u): bool => $u->isAdminEgap());

        if (! $admin) {
            $this->markTestSkipped('nenhum administrador do EGAP neste banco');
        }

        $this->actingAs($admin, User::GUARD);
        Filament::setCurrentPanel('egap');
    }

    public function test_lista_mostra_nome_da_cidade_e_cep_formatado(): void
    {
        $registro = CidUf::query()->with('idCidadeRef')->where('cd_cep_cidade', '>', 0)->firstOrFail();

        Livewire::test(ListCidUfs::class)
            ->assertOk()
            ->searchTable($registro->idCidadeRef->descricao)
            ->assertCanSeeTableRecords([$registro])
            ->assertSee(substr(str_pad((string) $registro->cd_cep_cidade, 8, '0', STR_PAD_LEFT), 0, 5) . '-');
    }

    public function test_cria_com_cep_mascarado_e_grava_so_os_digitos(): void
    {
        $cidade = $this->cidadeSemUf();

        Livewire::test(ListCidUfs::class)
            ->callAction('create', data: [
                'id_cidade' => $cidade->getKey(),
                'cd_uf' => 'ES',
                'cd_cep_cidade' => '29687-000',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(29687000, (int) CidUf::query()->where('id_cidade', $cidade->getKey())->value('cd_cep_cidade'));
    }

    public function test_nao_aceita_cidade_repetida_nem_cep_incompleto(): void
    {
        $existente = CidUf::query()->firstOrFail();

        Livewire::test(ListCidUfs::class)
            ->callAction('create', data: [
                'id_cidade' => $existente->id_cidade,
                'cd_uf' => 'ES',
                'cd_cep_cidade' => '2968',
            ])
            ->assertHasActionErrors(['id_cidade' => 'unique', 'cd_cep_cidade' => 'regex']);
    }

    public function test_edicao_abre_com_cep_formatado_e_mantem_a_propria_cidade(): void
    {
        $registro = CidUf::query()->where('cd_cep_cidade', '>', 0)->firstOrFail();
        $cep = str_pad((string) $registro->cd_cep_cidade, 8, '0', STR_PAD_LEFT);

        Livewire::test(ListCidUfs::class)
            ->mountAction(TestAction::make('edit')->table($registro))
            ->assertSchemaStateSet(['cd_cep_cidade' => substr($cep, 0, 5) . '-' . substr($cep, 5)])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame((int) $cep, (int) $registro->fresh()->cd_cep_cidade);
    }

    /** Toda cidade da base já tem UF/CEP; cria uma fictícia (a transação desfaz no fim). */
    private function cidadeSemUf(): Cidades
    {
        return Cidades::query()->create(['descricao' => 'CIDADE TESTE CIDUF']);
    }
}
