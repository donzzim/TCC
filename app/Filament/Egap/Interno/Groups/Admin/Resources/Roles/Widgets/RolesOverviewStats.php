<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\RolesResource;
use App\Models\egap\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Spatie\Permission\Models\Role;

class RolesOverviewStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $roles = Role::query()->where('guard_name', RolesResource::GUARD);
        $assignedRoleIds = DB::table(config('permission.table_names.model_has_roles'))
            ->select(config('permission.column_names.role_pivot_key') ?? 'role_id')
            ->where('model_type', (new User)->getMorphClass());

        return [
            Stat::make('Perfis cadastrados', Number::format((clone $roles)->count(), locale: 'pt_BR'))
                ->description('Total de perfis do EGAP')
                ->icon('heroicon-o-rectangle-stack')
                ->color('primary'),

            Stat::make('Usuários com perfil', Number::format(
                User::query()->whereHas('roles', fn ($query) => $query->where('guard_name', RolesResource::GUARD))->count(),
                locale: 'pt_BR',
            ))
                ->description('Usuários com ao menos um perfil')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Perfis sem usuários', Number::format(
                (clone $roles)->whereNotIn('id', $assignedRoleIds)->count(),
                locale: 'pt_BR',
            ))
                ->description('Nenhum usuário vinculado')
                ->icon('heroicon-o-user-minus')
                ->color('warning'),

            Stat::make('Perfis sem permissões', Number::format(
                (clone $roles)->whereDoesntHave('permissions')->count(),
                locale: 'pt_BR',
            ))
                ->description('Nenhuma permissão concedida')
                ->icon('heroicon-o-lock-closed')
                ->color('danger'),
        ];
    }
}
