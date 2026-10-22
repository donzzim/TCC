<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\PermissionsResource;
use App\Models\egap\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Spatie\Permission\Models\Permission;

class PermissionsOverviewStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $permissions = Permission::query()->where('guard_name', PermissionsResource::GUARD);
        $directPermissionIds = DB::table(config('permission.table_names.model_has_permissions'))
            ->select(config('permission.column_names.permission_pivot_key') ?? 'permission_id')
            ->where('model_type', (new User)->getMorphClass());

        return [
            Stat::make('Permissões cadastradas', Number::format((clone $permissions)->count(), locale: 'pt_BR'))
                ->description('Total de permissões do EGAP')
                ->icon('heroicon-o-key')
                ->color('primary'),

            Stat::make('Vinculadas a perfis', Number::format(
                (clone $permissions)->whereHas('roles')->count(),
                locale: 'pt_BR',
            ))
                ->description('Concedidas por ao menos um perfil')
                ->icon('heroicon-o-rectangle-stack')
                ->color('success'),

            Stat::make('Atribuídas diretamente', Number::format(
                (clone $permissions)->whereIn('id', clone $directPermissionIds)->count(),
                locale: 'pt_BR',
            ))
                ->description('Atribuição direta a pelo menos um usuário')
                ->icon('heroicon-o-user-plus')
                ->color('info'),

            Stat::make('Sem atribuição', Number::format(
                (clone $permissions)->whereDoesntHave('roles')->whereNotIn('id', $directPermissionIds)->count(),
                locale: 'pt_BR',
            ))
                ->description('Sem perfil ou usuário vinculado')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning'),
        ];
    }
}
