<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions;

use App\Filament\Egap\Interno\Clusters\AdminEgapCluster;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Schemas\PermissionsForm;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Tables\PermissionsTable;
use App\Models\egap\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

class PermissionsResource extends Resource
{
    public const GUARD = User::GUARD;

    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $cluster = AdminEgapCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Permissão';

    protected static ?string $pluralModelLabel = 'Permissões';

    protected static ?string $navigationLabel = 'Permissões';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdminEgap() || $user->hasRole('egap_super_admin'));
    }

    public static function form(Schema $schema): Schema
    {
        return PermissionsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
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
            'index' => ListPermissions::route('/'),
        ];
    }
}
