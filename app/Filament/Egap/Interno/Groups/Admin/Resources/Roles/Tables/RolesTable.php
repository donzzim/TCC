<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('name', 'Nome', true)
                    ->weight(FontWeight::Medium)
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),

                TableColumns::text('guard_name', 'Guard')
                    ->badge()
                    ->color('warning'),

                TableColumns::text('permissions_count', 'Permissões')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary'),

                TableColumns::text('users_count', 'Usuários')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),

                TableColumns::dateTime('updated_at', 'Atualizado em', format: 'd/m/Y')
                    ->description(fn ($record) => $record->updated_at->format('H:i')),
            ])
            ->recordActions([
                TableModalAction::make('gerenciar')
                    ->label('Gerenciar')
                    ->color('primary')
                    ->icon(Heroicon::Cog6Tooth)
                    ->modalHeading(fn (Role $record): string => 'Usuários de '.Str::headline($record->name))
                    ->modalContent(fn (Role $record): HtmlString => new HtmlString(
                        Livewire::mount('adm-egap.role-users',
                            ['roleId' => (int) $record->getKey()],
                            "egap-role-users-{$record->getKey()}",
                        )
                    ))
            ])
            ->defaultSort('name');
    }
}
