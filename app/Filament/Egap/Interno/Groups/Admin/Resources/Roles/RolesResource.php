<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Roles;

use App\Filament\Egap\Interno\Clusters\AdminEgapCluster;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Schemas\RolesForm;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Tables\RolesTable;
use App\Models\egap\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RolesResource extends Resource
{
    public const GUARD = User::GUARD;

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = AdminEgapCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Perfil de Acesso';

    protected static ?string $pluralModelLabel = 'Perfis de Acesso';

    protected static ?string $navigationLabel = 'Perfis de Acesso';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdminEgap() || $user->hasRole('egap_super_admin'));
    }


    public static function form(Schema $schema): Schema
    {
        return RolesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('guard_name', self::GUARD);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
        ];
    }
}
