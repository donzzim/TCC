<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('name', 'Nome', isFirstColumn: true)
                    ->weight(FontWeight::Medium)
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->description(fn ($record): string => $record->name),

                TableColumns::text('guard_name', 'Guard')
                    ->badge()
                    ->color('warning'),

                TableColumns::text('roles.name', 'Perfis')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->searchable(false)
                    ->sortable(false)
                    ->default(null)
                    ->placeholder('Nenhum')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),

                TableColumns::dateTime('updated_at', 'Atualizado em', format: 'd/m/Y')
                    ->description(fn ($record) => $record->updated_at->format('H:i')),
            ])
            ->defaultSort('name');
    }
}
