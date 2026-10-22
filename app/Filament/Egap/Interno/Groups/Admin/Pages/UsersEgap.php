<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Pages;

use App\Filament\Egap\Interno\Clusters\AdminEgapCluster;
use App\Filament\Egap\Interno\Permissions\Admin\AdminPage;
use App\Filament\Egap\Support\ExpandableImageColumn;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Helper\CpfHelper;
use App\Models\Cadastro\Tabelas;
use App\Models\egap\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ToggleComponent;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use NunoMaduro\Collision\Adapters\Phpunit\State;
use function Filament\Support\get_component_color_classes;

class UsersEgap extends AdminPage implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected const EMAIL_DOMAIN = '@tjes.jus.br';

    public const EGAP_ROLE = 'egap_user';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel = 'Usuários EGAP';
    protected static ?string $cluster = AdminEgapCluster::class;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $slug = 'users-egap';
    protected static ?string $title = 'Usuários EGAP';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';
    protected string $view = 'egap.livewire.support.filament-page-table';

    public static function getEloquentQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', fn(Builder $query): Builder => $query->where('name', static::EGAP_ROLE))
            ->withCount('lotacoes')
            ->with(['info', 'lotacoes.setor:id,setor,unidadeorganizacional']);
    }

    public static function egapRole(): ?\Spatie\Permission\Models\Role
    {
        return \Spatie\Permission\Models\Role::query()
            ->where('name', static::EGAP_ROLE)
            ->where('guard_name', User::GUARD)
            ->first();
    }

    private static function formSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Usuário')
                    ->tabs([
                        Tab::make('Dados do usuário')
                            ->icon(Heroicon::OutlinedUser)
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('login')
                                    ->label('Login')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('cpf')
                                    ->label('CPF')
                                    ->mask('999.999.999-99')
                                    ->stripCharacters(['.', '-'])
                                    ->required()
                                    ->maxLength(11)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('telefone')
                                    ->label('Telefone')
                                    ->tel()
                                    ->mask('(99) 99999-9999')
                                    ->stripCharacters(['(', ')', ' ', '-'])
                                    ->maxLength(11),
                                TextInput::make('matricula')
                                    ->label('Matrícula')
                                    ->maxLength(255),
                                TextInput::make('numero_funcional')
                                    ->label('Número funcional')
                                    ->maxLength(255),
                            ]),
                        Tab::make('Perfil')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->schema([
                                Grid::make(3)
                                    ->relationship('info')
                                    ->schema([
                                        Select::make('tipopessoa_id')
                                            ->label('Tipo de pessoa')
                                            ->options(fn (): array => Tabelas::query()
                                                ->where('grupo', 'TIPO_PESSOA')
                                                ->orderBy('descricao')
                                                ->pluck('descricao', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('cargo')
                                            ->label('Cargo')
                                            ->maxLength(255),
                                        TextInput::make('categoria_profissional')
                                            ->label('Categoria profissional')
                                            ->maxLength(255),
                                        TextInput::make('pis_pasep_nit')
                                            ->label('PIS/PASEP/NIT')
                                            ->maxLength(255),
                                        TextInput::make('contato')
                                            ->label('Contato')
                                            ->tel()
                                            ->maxLength(255),
                                        Textarea::make('setor_orgao')
                                            ->label('Setor/órgão')
                                            ->rows(2),
                                        Select::make('raca')
                                            ->label('Raça/cor')
                                            ->options(fn (): array => Tabelas::query()
                                                ->where('grupo', 'RACA_COR')
                                                ->orderBy('descricao')
                                                ->pluck('descricao', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload(),
                                        Select::make('sexo')
                                            ->label('Sexo')
                                            ->options(fn (): array => Tabelas::query()
                                                ->where('grupo', 'SEXO')
                                                ->orderBy('descricao')
                                                ->pluck('descricao', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload(),
                                        Select::make('genero')
                                            ->label('Gênero')
                                            ->options(fn (): array => Tabelas::query()
                                                ->where('grupo', 'IDENTIDADE_GENERO')
                                                ->orderBy('descricao')
                                                ->pluck('descricao', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload(),
                                        Toggle::make('chefia')
                                            ->label('Exerce chefia')
                                            ->default(false)
                                            ->inline(false),
                                        Toggle::make('estrangeiro')
                                            ->label('Estrangeiro')
                                            ->default(false)
                                            ->inline(false),
                                        Repeater::make('pcd_necessidade')
                                            ->label('Deficiências e necessidades')
                                            ->schema([
                                                Select::make('tipo')
                                                    ->label('Tipo')
                                                    ->options(fn (): array => Tabelas::query()
                                                        ->where('grupo', 'TIPO_DEFICIENCIA')
                                                        ->orderBy('descricao')
                                                        ->pluck('descricao', 'id')
                                                        ->toArray())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                TextInput::make('descricao_necessidade')
                                                    ->label('Necessidade específica')
                                                    ->maxLength(255),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->addActionLabel('Adicionar deficiência')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(static::getEloquentQuery())
            ->defaultSort('name')
            ->columns([
                TableColumns::text('name', 'Nome', true)
                    ->searchable()
                    ->sortable(),
                TableColumns::text('login', 'Login')
                    ->sortable(false)
                    ->badge(),
                ExpandableImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(Storage::disk('public')->url('placeholder.svg'))
                    ->extraImgAttributes(fn (): array => [
                        'onerror' => "this.onerror=null;this.src='" . Storage::disk('public')->url('placeholder.svg') . "'",
                    ])
                    ->alignStart()
                    ->expandModalHeading(fn (User $record): string => $record->name),
                TableColumns::text('email', 'E-mail')
                    ->sortable(false),
                TableColumns::text('cpf', 'CPF')
                    ->badge()
                    ->color('gray')
                    ->sortable(false)
                    ->formatStateUsing(fn(?string $state): string => CpfHelper::format($state)),
                ToggleColumn::make('ativo')
                    ->label('Ativo')
                    ->onColor('success')
                    ->offColor('danger')
                    ->updateStateUsing(function (User $record, bool $state): bool {
                        $record->ativo = $state;
                        $record->save();

                        return $state;
                    }),
            ])
            ->filters([
                Filter::make('sem_lotacao')
                    ->label('Sem lotação')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->doesntHave('lotacoes')),
                Filter::make('sem_principal')
                    ->label('Com lotação, sem principal')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query
                        ->has('lotacoes')
                        ->whereDoesntHave('lotacoes', fn(Builder $q): Builder => $q->where('principal', true))),
                Filter::make('multiplas_principais')
                    ->label('Mais de uma principal')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query
                        ->whereHas('lotacoes', fn(Builder $q) => $q->where('principal', true), '>', 1)),
                TernaryFilter::make('ativo')
                    ->label('Situação')
                    ->native(false)
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('gray')
                        ->schema(fn(Schema $schema): Schema => static::formSchema($schema))
                        ->databaseTransaction()
                        ->modalWidth(Width::SixExtraLarge)
                        ->modalHeading(fn (User $record): string => "Editar informações do Usuário EGAP #{$record->id}")
                        ->modalSubmitActionLabel('Salvar alterações'),
                    TableModalAction::make('lotacoes')
                        ->label('Lotações')
                        ->color('gray')
                        ->icon(Heroicon::OutlinedBuildingOffice2)
                        ->modalHeading(fn($record): string => 'Lotações de ' . $record->name)
                        ->modalContent(fn($record): HtmlString => new HtmlString(
                            Livewire::mount('adm-egap.user-lotacao',
                                ['userId' => (int) $record->id],
                                "egap-user-lotacao-{$record->getKey()}",
                            )
                        )),
                    Action::make('remover_acesso')
                        ->label('Remover Acesso')
                        ->color('danger')
                        ->icon(Heroicon::OutlinedUserMinus)
                        ->requiresConfirmation()
                        ->modalHeading(fn($record): string => 'Remover acesso de ' . $record->name)
                        ->modalDescription('O usuário perderá o acesso ao EGAP.')
                        ->action(function (User $record): void {
                            $record->removeRole(static::egapRole());

                            $this->resetTable();

                            Notification::make()
                                ->title('Acesso removido com sucesso.')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->headerActions([
                Action::make('acessos_usersegap')
                    ->label('Acessos')
                    ->modalHeading('Liberar Acessos')
                    ->icon(Heroicon::UserGroup)
                    ->modalSubmitActionLabel('Liberar')
                    ->schema([
                        Select::make('users')
                            ->hiddenLabel()
                            ->helperText('Usuários que não possuem acesso ao EGAP')
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->options(fn () => User::query()
                                ->whereDoesntHave('roles', fn (Builder $query): Builder => $query->where('name', static::EGAP_ROLE))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()),
                    ])
                    ->action(function (array $data): void {
                        $role = static::egapRole();

                        User::query()
                            ->whereIn('id', $data['users'])
                            ->get()
                            ->each(fn (User $user) => $user->assignRole($role));

                        $this->resetTable();

                        Notification::make()
                            ->title('Acesso liberado com sucesso.')
                            ->success()
                            ->send();
                    })
            ], HeaderActionsPosition::Adaptive)
            ->toolbarActions([]);
    }
}
