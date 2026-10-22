<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use App\Filament\App\Resources\MeusDocumentosResource;
use App\Filament\Exports\UsersExporter;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoPessoas;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use App\Models\Recadastro;
use App\Models\Setores;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ExportAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $puralModelLabel = 'Usuários';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuários';
    protected static string | UnitEnum | null $navigationGroup = 'Controle de Acesso';
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getFormfields(): array
    {
        return [
            Section::make('Usuário')
                ->schema([
                    Forms\Components\TextInput::make('cpf')
                        ->label('CPF')
                        ->mask('999.999.999-99')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->rule(function (Get $get) {
                            return function ($attribute, $value, $fail) use ($get) {
                                $sanitized = $value ? preg_replace('/\D/', '', $value) : null;
                                $existente = User::where('cpf', $sanitized)->whereNot('id', $get('id') ?? -1)->exists();
                                // dd($existente, $value, $sanitized);
                                if ($existente) {
                                    $fail('CPF já cadastrado');
                                    return;
                                }
                            };
                        })
                        ->live(200)
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {

                                $cpf = preg_replace("/[^0-9]/", "", $state);
                                $recadastro = Recadastro::where('cpf', $cpf)->first();
                                if ($recadastro) {
                                    $set('name', $recadastro->nome);
                                    $set('email', $recadastro->email);
                                    $set('telefone', $recadastro->telefone_celular);
                                }
                            }
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('login')
                        ->label('Login')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1)
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('name')
                        ->label('Nome Completo')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->rule(function (Get $get) {
                            return function ($attribute, $value, $fail) use ($get) {
                                $existente = User::where('email', $value)->whereNot('id', $get('id') ?? -1)->exists();
                                if ($existente) {
                                    $fail('Email já existente');
                                    return;
                                }
                            };
                        })
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('telefone')
                        ->label('Telefone')
                        ->mask('(99)99999-9999')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->required()
                        ->maxLength(255)
                        ->hiddenOn('edit')
                        ->visibleOn('create')
                        ->columnSpan(1),
                ])
                ->columns(4),

            Forms\Components\Select::make('roles')
                ->label('Perfil')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->required()
                ->searchable()
                ->default([6])
                ->columnSpanFull(),

            Section::make('Setores do Usuário')
                ->schema([
                    Forms\Components\Repeater::make('lotacao')
                        ->hiddenLabel()
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('setor_id')
                                ->label('Setor')
                                ->inlineLabel()
                                ->relationship('setor', 'setor')
                                ->preload()
                                ->searchable()
                                ->columnSpan(3),
                            Forms\Components\Toggle::make('principal')
                                ->label('Setor Principal')
                                ->inlineLabel()
                                ->inline(false)
                                ->columnSpan(1),
                        ])
                        ->reorderable(false)
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Adicionar setor'),
                ])
                ->columnSpanFull()
        ];
    }
    public static function getFormfieldsCadastro(): array
    {
        return [
            Section::make('Usuário')
                ->schema([
                    Forms\Components\TextInput::make('cpf')
                        ->label('CPF')
                        ->mask('999.999.999-99')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->rule(function (Get $get) {
                            return function ($attribute, $value, $fail) use ($get) {
                                $sanitized = $value ? preg_replace('/\D/', '', $value) : null;
                                $existente = User::where('cpf', $sanitized)->whereNot('id', $get('id') ?? -1)->exists();
                                // dd($existente, $value, $sanitized);
                                if ($existente) {
                                    $fail('CPF já cadastrado');
                                    return;
                                }
                            };
                        })
                        ->live(200)
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {

                                $cpf = preg_replace("/[^0-9]/", "", $state);
                                $recadastro = Recadastro::where('cpf', $cpf)->first();
                                if ($recadastro) {
                                    $set('name', $recadastro->nome);
                                    $set('email', $recadastro->email);
                                    $set('telefone', $recadastro->telefone_celular);
                                }
                            }
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('name')
                        ->label('Nome Completo')
                        ->required()
                        ->live(debounce: 1000)
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->afterStateUpdated(function (Set $set, $state) {
                            $parts = explode(' ', strtolower($state));
                            $firstName = $parts[0];
                            $lastName = end($parts);

                            $login = "{$firstName}.{$lastName}";
                            $set('login', $login);
                        }),

                    Forms\Components\TextInput::make('login')
                        ->label('Login')
                        // ->readOnly()
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1)
                        ->live()
                        ->unique(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->rule(function (Get $get) {
                            return function ($attribute, $value, $fail) use ($get) {
                                $existente = User::where('email', $value)->whereNot('id', $get('id') ?? -1)->exists();
                                if ($existente) {
                                    $fail('Email já existente');
                                    return;
                                }
                            };
                        })
                        ->maxLength(255)
                        ->unique()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('telefone')
                        ->label('Telefone')
                        ->mask('(99)99999-9999')
                        ->columnSpan(1),
                ])
                ->columns(4),
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::getFormfields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/admin-tie.png'))
                    ->circular()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->getStateUsing(function ($record) {
                        return $record->name ?? '—';
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->formatStateUsing(fn($state) => $state ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $state) : '')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('perfil.tipopessoa.descricao')
                    ->label('Vinculo')
                    ->getStateUsing(function ($record) {
                        // dd($record->perfil->tipopessoa->descricao ?? 'Sem vínculo');
                        return $record->perfil->tipopessoa->descricao ?? 'Sem vínculo';
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('perfil.raca')
                    ->label('Raça / Cor')
                    ->getStateUsing(function ($record) {
                        return TipoRaca::where('id', $record->perfil->raca ?? -1)->first()->descricao ?? 'Sem informação';
                    })
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('perfil.sexo')
                    ->label('Sexo')
                    ->getStateUsing(function ($record) {
                        return TipoSexo::where('id', $record->perfil->sexo ?? -1)->first()->descricao ?? 'Sem informação';
                    })
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('perfil.pcd')
                    ->label('PCD')
                    ->getStateUsing(function ($record) {
                        if ($record->perfil->pcd ?? false && is_array($record->perfil->pcd) && count($record->perfil->pcd) > 0) {
                            return 'Sim';
                        }
                        return 'Não';
                    })
                    ->badge()
                    ->tooltip(function ($record) {
                        if (isset($record->perfil->pcd) && is_array($record->perfil->pcd) && count($record->perfil->pcd) > 0) {
                            //estrutura de pcd ex: [{"tipo":"3011","descricao_necessidade":"<p>a<\/p>"}]
                            $descricoes = array_map(function ($pcd) {
                                $tipo = TipoDeficiencia::where('id', (int)($pcd['tipo']))->pluck('descricao')->toArray()[0] ?? 'Tipo Desconecido';
                                $descricao_necessidade = $pcd['descricao_necessidade'] ?? '';
                                return $tipo . ': ' . $descricao_necessidade;
                            }, $record->perfil->pcd);
                            return implode(' | ', $descricoes);
                        }
                        return 'Sem descrição';
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Perfil')
                    ->searchable()->listWithLineBreaks()
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('ativo')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lotacao.setor.setor')
                    ->wrap()
                    ->listWithLineBreaks()
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('ativo')
                    ->label('Ativo')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('ativo', 1))
                    ->columnSpan(2)
                    ->default(false),
                Filter::make('pcd')
                    ->label('PCD')
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('perfil', function (Builder $perfilQuery) {
                            $perfilQuery->whereNotNull('pcd')->where('pcd', '!=', '[]');
                        });
                    })
                    ->columnSpan(2)
                    ->default(false),

                SelectFilter::make('setor')
                    ->label('Setor')
                    ->options(fn(): array => Setores::orderBy('setor')->pluck('setor', 'id')->all())
                    ->searchable()
                    ->multiple()
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['values'] ?? null;

                        if ($value === [] || $value === NULL) {
                            return $query;
                        }

                        return $query->whereHas(
                            'lotacao.setor',
                            fn(Builder $setorQuery): Builder => $setorQuery->whereIn('id', $value),
                        );
                    })
                    ->columnSpan(3),

                SelectFilter::make('roles')
                    ->label('Perfil')
                    ->options(fn(): array => DB::table('roles')->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->multiple()
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['values'] ?? null;

                        if ($value === [] || $value === NULL) {
                            return $query;
                        }

                        return $query->whereHas(
                            'roles',
                            fn(Builder $rolesQuery): Builder => $rolesQuery->whereIn('id', $value),
                        );
                    })
                    ->columnSpan(3),

                SelectFilter::make('vinculo')
                    ->label('Vínculo')
                    ->options(fn(): array => TipoPessoas::orderBy('descricao')->pluck('descricao', 'id')->all())
                    ->searchable()
                    ->multiple()
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['values'] ?? null;

                        // dd($value);

                        // dd(TipoPessoas::whereIn('id', $value)->pluck('descricao', 'id')->all());

                        if ($value === [] || $value === NULL) {
                            return $query;
                        }

                        return $query->whereHas(
                            'perfil',
                            fn(Builder $tipopessoaQuery): Builder => $tipopessoaQuery->whereIn('tipopessoa_id', $value),
                        );
                    })
                    ->columnSpan(3),

                SelectFilter::make('sexo')
                    ->label('Sexo')
                    ->options(fn(): array => TipoSexo::query()
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->all())
                    ->searchable()
                    ->multiple()
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['values'] ?? null;

                        if ($value === [] || $value === NULL) {
                            return $query;
                        }

                        return $query->whereHas(
                            'perfil',
                            fn(Builder $perfilQuery): Builder => $perfilQuery->whereIn('sexo', $value),
                        );
                    })
                    ->columnSpan(3),


                SelectFilter::make('raca')
                    ->label('Raça/Cor')
                    ->options(fn(): array => TipoRaca::query()
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->all())
                    ->searchable()
                    ->multiple()
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['values'] ?? null;

                        if ($value === [] || $value === NULL) {
                            return $query;
                        }

                        return $query->whereHas(
                            'perfil',
                            fn(Builder $perfilQuery): Builder => $perfilQuery->whereIn('raca', $value),
                        );
                    })
                    ->columnSpan(3),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(8)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->headerActions([
                ExportAction::make('export')
                    ->exporter(UsersExporter::class)
                    ->label('Exportar')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->actions([
                Action::make('alterar_senha')
                    ->hiddenLabel()
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->modalHeading('Alterar Senha do Usuário')
                    ->modalSubmitActionLabel('Salvar Nova Senha')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Nova Senha')
                            ->password()
                            ->revealable() // Adiciona um ícone de "olho" para ver a senha
                            ->required()
                            ->rule(Password::defaults()) // Usa as regras de senha padrão do Laravel
                            ->confirmed() // Requer que o campo 'password_confirmation' seja igual
                            ->dehydrated(fn($state) => filled($state)), // Só envia para o backend se estiver preenchido

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Nova Senha')
                            ->password()
                            ->required()
                            ->dehydrated(false), // Garante que este campo nunca seja salvo no banco
                    ])
                    ->action(function (array $data, User $record) {
                        // A lógica a ser executada quando o formulário for submetido
                        $record->update([
                            'password' => Hash::make($data['password']),
                        ]);

                        // Envia uma notificação de sucesso
                        Notification::make()
                            ->title('Senha alterada com sucesso!')
                            ->success()
                            ->send();
                    }),

                // Action::make('inserir_documentos')
                //     ->hiddenLabel()
                //     ->icon('heroicon-o-key')
                //     ->color('success')
                //     ->modalHeading('Inserir documentos do usuario')
                //     ->modalSubmitActionLabel('Salvar documentos')
                //     ->form(MeusDocumentosResource::getFormFields())
                //     ->action(function (array $data, User $record) {
                //         // A lógica a ser executada quando o formulário for submetido


                //         // Envia uma notificação de sucesso
                //         Notification::make()
                //             ->title('Senha alterada com sucesso!')
                //             ->success()
                //             ->send();
                //     }),
                EditAction::make()
                    ->hiddenLabel(),
                ViewAction::make()
                    ->hiddenLabel(),
                Action::make('delete')
                    ->tooltip('Deletar Usuario')
                    ->requiresConfirmation('Está prestes a apagar um usuario')
                    ->action(function (User $record) {
                        $erro = static::deleteUserRecord($record);

                        if ($erro) {
                            Notification::make()
                                ->title('Usuario nao excluido')
                                ->body($erro)
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Usuario excluido com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->hiddenLabel(),

            ])
            ->toolbarActions([
                BulkAction::make('delete')
                    ->label('Excluir selecionados')
                    ->requiresConfirmation()
                    ->modalHeading('Excluir usuarios selecionados')
                    ->modalDescription('Usuarios com vinculos academicos ou outros registros relacionados nao serao excluidos.')
                    ->modalSubmitActionLabel('Excluir')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->action(function (Collection $records): void {
                        $excluidos = 0;
                        $bloqueados = [];

                        foreach ($records as $record) {
                            $erro = static::deleteUserRecord($record);

                            if ($erro) {
                                $bloqueados[] = ($record->name ?: "ID {$record->id}") . ": {$erro}";
                                continue;
                            }

                            $excluidos++;
                        }

                        if ($excluidos > 0) {
                            Notification::make()
                                ->title("{$excluidos} usuario(s) excluido(s) com sucesso!")
                                ->success()
                                ->send();
                        }

                        if ($bloqueados !== []) {
                            Notification::make()
                                ->title('Alguns usuarios nao foram excluidos')
                                ->body(implode("\n", array_slice($bloqueados, 0, 5)) . (count($bloqueados) > 5 ? "\n..." : ''))
                                ->warning()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('Trocar Roles')
                    ->label('Editar Roles')
                    ->form([
                        Forms\Components\Select::make('roles')
                            ->label('Roles a serem atribuídas')
                            ->options(function ($get) {
                                $selectedRoles = $get('roles_r') ?? [];
                                return DB::table('roles')
                                    ->whereNotIn('name', $selectedRoles)->get()->pluck('name', 'name')->all();
                            })
                            ->multiple()
                            ->live()
                            ->preload()
                            ->searchable(),

                        Forms\Components\Select::make('roles_r')
                            ->label('Roles a serem removidas')
                            ->options(function ($get) {
                                $selectedRoles = $get('roles') ?? [];
                                return DB::table('roles')
                                    ->whereNotIn('name', $selectedRoles)->get()->pluck('name', 'name')->all();
                            })
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        // dd($data);
                        // dd($data);
                        foreach ($records as $record) {
                            $record->removeRole($data['roles_r']);
                            $record->assignRole($data['roles']);
                        }
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),

            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = number_format(static::getModel()::count(), 0, ',', '.');
        return $count;
    }

    protected static function deleteUserRecord(User $user): ?string
    {
        if ($user->alunoCurso()->exists()) {
            return 'Possui inscricoes/vinculos em cursos. Remova esses vinculos antes de excluir o usuario.';
        }

        try {
            return $user->delete()
                ? null
                : 'Nao foi possivel excluir o usuario.';
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return 'Possui registros relacionados em outras tabelas. Remova os vinculos antes de excluir o usuario.';
            }

            throw $exception;
        }
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'cpf', 'telefone'];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Usuário' => $record->name,
            'Email' => $record->email,
        ];
    }
}
