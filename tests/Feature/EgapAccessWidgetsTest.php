<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Widgets\PermissionsOverviewStats;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Widgets\RolesOverviewStats;
use App\Models\egap\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EgapAccessWidgetsTest extends TestCase
{
    public function test_stats_count_only_egap_roles_permissions_and_users(): void
    {
        config()->set('database.connections.widget_stats_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'widget_stats_test');
        DB::purge('widget_stats_test');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        DB::table('users')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'egap_one', 'guard_name' => 'egap'],
            ['id' => 2, 'name' => 'egap_two', 'guard_name' => 'egap'],
            ['id' => 3, 'name' => 'egap_three', 'guard_name' => 'egap'],
            ['id' => 4, 'name' => 'web_one', 'guard_name' => 'web'],
        ]);
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'egap_one', 'guard_name' => 'egap'],
            ['id' => 2, 'name' => 'egap_two', 'guard_name' => 'egap'],
            ['id' => 3, 'name' => 'egap_three', 'guard_name' => 'egap'],
            ['id' => 4, 'name' => 'egap_four', 'guard_name' => 'egap'],
            ['id' => 5, 'name' => 'web_one', 'guard_name' => 'web'],
        ]);
        DB::table('role_has_permissions')->insert([
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 4],
            ['role_id' => 4, 'permission_id' => 5],
        ]);

        $egapUserType = (new User)->getMorphClass();
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => $egapUserType, 'model_id' => 1],
            ['role_id' => 3, 'model_type' => $egapUserType, 'model_id' => 2],
            ['role_id' => 2, 'model_type' => \App\Models\User::class, 'model_id' => 3],
            ['role_id' => 4, 'model_type' => \App\Models\User::class, 'model_id' => 3],
        ]);
        DB::table('model_has_permissions')->insert([
            ['permission_id' => 2, 'model_type' => $egapUserType, 'model_id' => 1],
            ['permission_id' => 4, 'model_type' => $egapUserType, 'model_id' => 2],
            ['permission_id' => 3, 'model_type' => \App\Models\User::class, 'model_id' => 3],
            ['permission_id' => 5, 'model_type' => \App\Models\User::class, 'model_id' => 3],
        ]);

        $rolesWidget = new class extends RolesOverviewStats
        {
            public function stats(): array
            {
                return $this->getStats();
            }
        };
        $permissionsWidget = new class extends PermissionsOverviewStats
        {
            public function stats(): array
            {
                return $this->getStats();
            }
        };
        $values = fn (array $stats): array => collect($stats)
            ->mapWithKeys(fn (Stat $stat): array => [$stat->getLabel() => $stat->getValue()])
            ->all();

        $this->assertSame([
            'Perfis cadastrados' => '3',
            'Usuários com perfil' => '2',
            'Perfis sem usuários' => '1',
            'Perfis sem permissões' => '2',
        ], $values($rolesWidget->stats()));
        $this->assertSame([
            'Permissões cadastradas' => '4',
            'Vinculadas a perfis' => '2',
            'Atribuídas diretamente' => '2',
            'Sem atribuição' => '1',
        ], $values($permissionsWidget->stats()));
    }
}
