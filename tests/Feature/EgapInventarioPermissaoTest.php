<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\AtividadeInventarioResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\EditInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\RelationManagers\UnidadesInventariadasRelationManager;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use App\Models\egap\User;
use Database\Seeders\Spatie\EgapRolesPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Abrir (criar, editar, excluir) inventário é exclusivo de quem tem a
 * permission `abrir_inventario` (role `egap_inventario`). `egap_patrimonio`
 * só consulta. Conferir os bens continua livre e não passa por aqui.
 */
class EgapInventarioPermissaoTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EgapRolesPermissionsSeeder::class);
        Filament::setCurrentPanel('egap');
    }

    private function usuarioCom(array $roles): User
    {
        $user = User::query()->where('ativo', 1)->orderBy('id')->get()
            ->first(fn (User $u): bool => ! $u->isAdminEgap());

        $user->syncRoles([User::ROLE_USER, ...$roles]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user->fresh(), User::GUARD);

        return $user;
    }

    public function test_seeder_cria_a_role_com_a_permissao(): void
    {
        $user = $this->usuarioCom(['egap_inventario']);

        $this->assertTrue($user->fresh()->can('abrir_inventario'));
    }

    public function test_egap_patrimonio_so_consulta_o_inventario(): void
    {
        $this->usuarioCom(['egap_patrimonio']);
        $inventario = Inventario::query()->latest('id')->firstOrFail();

        foreach ([InventarioResource::class, InventarioUnidadeResource::class] as $resource) {
            $this->assertTrue($resource::canViewAny(), $resource);
            $this->assertFalse($resource::canCreate(), $resource);
        }

        $this->assertFalse(InventarioResource::canEdit($inventario));
        $this->assertFalse(InventarioResource::canDelete($inventario));
        $this->assertFalse(InventarioResource::canDeleteAny());
        $this->get(InventarioResource::getUrl('create'))->assertForbidden();
        $this->get(InventarioResource::getUrl('edit', ['record' => $inventario]))->assertForbidden();

        Livewire::test(ListInventarios::class)
            ->assertActionHidden(TestAction::make('atualizar_situacao')->table($inventario));

        // o resto do Patrimônio continua igual
        $this->assertTrue(AtividadeInventarioResource::canCreate());
    }

    public function test_egap_inventario_abre_o_inventario_e_nao_ve_o_resto_do_patrimonio(): void
    {
        $this->usuarioCom(['egap_inventario']);
        // sem conferência registrada: inventário com conferência não pode ser excluído por ninguém
        $inventario = Inventario::query()->create(['num_inventario' => '99', 'ano_inventario' => 2026, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);
        $unidade = InventarioUnidade::query()->latest('id')->firstOrFail();

        $this->assertTrue(PatrimonioCluster::canAccess());
        $this->assertTrue(InventarioResource::canCreate());
        $this->assertTrue(InventarioResource::canEdit($inventario));
        $this->assertTrue(InventarioResource::canDelete($inventario));
        $this->assertTrue(InventarioUnidadeResource::canEdit($unidade));
        $this->get(InventarioResource::getUrl('create'))->assertOk();

        Livewire::test(ListInventarios::class)
            ->assertActionVisible(TestAction::make('atualizar_situacao')->table($inventario));

        $this->assertFalse(BemMovelResource::canViewAny());
        $this->assertFalse(AtividadeInventarioResource::canViewAny());
    }

    public function test_sem_role_nao_entra_no_patrimonio(): void
    {
        $this->usuarioCom([]);

        $this->assertFalse(PatrimonioCluster::canAccess());
        $this->assertFalse(InventarioResource::canViewAny());
    }

    public function test_tabela_de_unidades_e_so_leitura_para_egap_patrimonio(): void
    {
        $this->usuarioCom(['egap_patrimonio']);
        $inventario = Inventario::query()->has('unidadesInventariadas')->latest('id')->firstOrFail();

        Livewire::test(UnidadesInventariadasRelationManager::class, ['ownerRecord' => $inventario, 'pageClass' => EditInventario::class])
            ->assertOk()
            ->assertActionHidden(TestAction::make('create')->table())
            ->assertActionHidden(TestAction::make('edit')->table($inventario->unidadesInventariadas()->first()));
    }
}
