<?php

namespace Database\Seeders;

use App\Models\egap\User;
use App\Models\egap\UserInfo;
use App\Models\egap\UserLotacao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserTestSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->almoxarifadoEgapUser();
        $this->patrimonioEgapUser();
        $this->externoEgapUser();
        $this->adminEgapUser();
        $this->superAdminEgapUser();
    }

    public function almoxarifadoEgapUser(): void
    {
        Role::findOrCreate('egap_user', User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_access', User::GUARD));

        Role::findOrCreate('egap_almoxarifado', User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_almoxarifado_access', User::GUARD));

        $user = User::query()->firstOrNew(['login' => 'egap_almoxarifado']);

        $user->forceFill([
            'name' => 'Almoxarifado User',
            'email' => 'almoxarifado_email@tjes.jus.br',
            'cpf' => '98765432100',
            'ativo' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
        ])->save();

        UserInfo::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['cargo' => 'Almoxarifado Boss'],
        );

        UserLotacao::query()->updateOrCreate(
            ['user_id' => $user->id, 'setor_id' => 799],
            ['principal' => 1, 'atualizado_por' => 1],
        );

        $user->syncRoles(['egap_user', 'egap_almoxarifado']);
    }

    public function patrimonioEgapUser(): void
    {
        Role::findOrCreate('egap_user', User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_access', User::GUARD));

        Role::findOrCreate('egap_patrimonio', User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_patrimonio_access', User::GUARD));

        $user = User::query()->firstOrNew(['login' => 'egap_patrimonio']);

        $user->forceFill([
            'name' => 'Patrimonio User',
            'email' => 'patrimonio_email@tjes.jus.br',
            'cpf' => '12345678900',
            'ativo' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
        ])->save();

        UserInfo::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['cargo' => 'Patrimonio Boss'],
        );

        UserLotacao::query()->updateOrCreate(
            ['user_id' => $user->id, 'setor_id' => 1239],
            ['principal' => 1, 'atualizado_por' => 1],
        );

        $user->syncRoles(['egap_user', 'egap_patrimonio']);
    }

    public function adminEgapUser(): void
    {
        Role::findOrCreate('egap_user', User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_access', User::GUARD));

        Role::findOrCreate(User::ROLE_ADMIN, User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_superadmin', User::GUARD));

        $user = User::query()->firstOrNew(['login' => 'egap_admin']);

        $user->forceFill([
            'name' => 'Admin EGAP User',
            'email' => 'admin_egap_email@tjes.jus.br',
            'cpf' => '11122233300',
            'ativo' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
        ])->save();

        UserInfo::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['cargo' => 'Admin EGAP Boss'],
        );

        UserLotacao::query()->updateOrCreate(
            ['user_id' => $user->id, 'setor_id' => 1239],
            ['principal' => 1, 'atualizado_por' => 1],
        );

        $user->syncRoles(['egap_user', User::ROLE_ADMIN]);
    }

    public function superAdminEgapUser(): void
    {
        $role = Role::findOrCreate(User::ROLE_SUPER_ADMIN, User::GUARD);
        $role->givePermissionTo(Permission::query()->where('guard_name', User::GUARD)->get());

        User::query()->findOrFail(1)->assignRole($role);
    }

    public function externoEgapUser(): void
    {
        Role::findOrCreate('egap_user', User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_access', User::GUARD));

        Role::findOrCreate(User::ROLE_EXTERNO, User::GUARD)
            ->givePermissionTo(Permission::findOrCreate('egap_externo_access', User::GUARD));

        $user = User::query()->firstOrNew(['login' => 'egap_externo']);

        $user->forceFill([
            'name' => 'Externo User',
            'email' => 'externo_email@tjes.jus.br',
            'cpf' => '12345678999',
            'ativo' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
        ])->save();

        UserInfo::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['cargo' => 'Externo Boss'],
        );

        UserLotacao::query()->updateOrCreate(
            ['user_id' => $user->id, 'setor_id' => 2941],
            ['principal' => 1, 'atualizado_por' => 1],
        );
        UserLotacao::query()->updateOrCreate(
            ['user_id' => $user->id, 'setor_id' => 2957],
            ['principal' => 1, 'atualizado_por' => 1],
        );

        $user->syncRoles([User::ROLE_USER, User::ROLE_EXTERNO]);
    }
}
