<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Schemas;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\PermissionsResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class PermissionsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->description('Permissões utilizadas pelo sistema do EGAP.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->helperText('Use o padrão ação_recurso, por exemplo: view_any_bem_movel.')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('guard_name', PermissionsResource::GUARD),
                            ),

                        Hidden::make('guard_name')
                            ->default(PermissionsResource::GUARD)
                            ->dehydrateStateUsing(fn (): string => PermissionsResource::GUARD),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make('Perfis de Acesso')
                    ->description('Selecione os perfis que recebem esta permissão.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->hiddenLabel()
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('guard_name', PermissionsResource::GUARD)
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
