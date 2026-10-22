<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Schemas;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\RolesResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class RolesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->description('Perfis de acesso utilizados pelo sistema do EGAP.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('guard_name', RolesResource::GUARD),
                            ),

                        Hidden::make('guard_name')
                            ->default(RolesResource::GUARD)
                            ->dehydrateStateUsing(fn (): string => RolesResource::GUARD),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make('Permissões')
                    ->description('Selecione as permissões concedidas a este perfil.')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->hiddenLabel()
                            ->relationship(
                                name: 'permissions',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('guard_name', RolesResource::GUARD)
                                    ->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => Str::headline($record->name))
                            ->searchable()
                            ->bulkToggleable()
                            ->columns([
                                'sm' => 1,
                                'lg' => 2,
                                'xl' => 3,
                            ])
                            ->gridDirection('row'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
