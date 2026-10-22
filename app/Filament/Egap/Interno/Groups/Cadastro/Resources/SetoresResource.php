<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource\Pages\ListSetores;
use App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource\Widgets\SetoresCountStats;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\Setores;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;
use Illuminate\Database\Eloquent\Builder;

class SetoresResource extends CadastroResource
{
    protected static ?string $model = Setores::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Setores';

    protected static ?string $modelLabel = 'Setor';

    protected static ?string $pluralModelLabel = 'Setores';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->description('Dados principais usados para identificar e organizar o setor.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('unidadeorganizacional')
                                    ->label('Unidade Organizacional')
                                    ->options(fn (): array => Setores::query()
                                        ->whereColumn('id', 'codigodauo')
                                        ->whereKeyNot(0)
                                        ->orderBy('unidadeorganizacional')
                                        ->pluck('unidadeorganizacional', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateHydrated(function (Select $component, ?Setores $record): void {
                                        if ($record) {
                                            $component->state($record->codigodauo);
                                        }
                                    })
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        $set('codigopai', null);
                                        $set('codigodauo', $state);
                                    })
                                    ->dehydrateStateUsing(fn (mixed $state): ?string => filled($state)
                                        ? Setores::query()->find($state)?->unidadeorganizacional
                                        : null)
                                    ->required()
                                    ->columnSpan(6),

                                Select::make('codigopai')
                                    ->label('Setor Pai')
                                    ->options(function (Get $get): array {
                                        $unidadeOrganizacionalId = $get('unidadeorganizacional');

                                        if (blank($unidadeOrganizacionalId)) {
                                            return [];
                                        }

                                        $unidadeOrganizacional = Setores::query()->find($unidadeOrganizacionalId);

                                        if (! $unidadeOrganizacional) {
                                            return [];
                                        }

                                        $filhos = Setores::query()
                                            ->where('codigodauo', $unidadeOrganizacionalId)
                                            ->whereKeyNot($unidadeOrganizacionalId)
                                            ->orderBy('setor')
                                            ->pluck('setor', 'id')
                                            ->toArray();

                                        return [
                                            $unidadeOrganizacional->id => "Diretamente em {$unidadeOrganizacional->unidadeorganizacional}",
                                        ] + $filhos;
                                    })
                                    ->searchable()
                                    ->disabled(fn (Get $get): bool => blank($get('unidadeorganizacional')))
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(6),

                                Hidden::make('codigodauo'),

                                TextInput::make('setor')
                                    ->label('Setor')
                                    ->placeholder('Informe o nome do setor')
                                    ->required()
                                    ->disabled(fn (Get $get): bool => blank($get('unidadeorganizacional')))
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Códigos e contato')
                    ->description('Dados de integração, ordenação e contato do setor.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('ordem')
                                    ->label('Ordem')
                                    ->integer()
                                    ->minValue(0)
                                    ->columnSpan(4),

                                TextInput::make('centrocusto')
                                    ->label('Centro de Custo')
                                    ->numeric()
                                    ->columnSpan(4),

                                TextInput::make('cns')
                                    ->label('CNS')
                                    ->integer()
                                    ->minValue(0)
                                    ->columnSpan(4),

                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Classificação')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Radio::make('funcao')
                                    ->label('Função')
                                    ->options([
                                        'V' => 'Vara',
                                        'C' => 'Comarca',
                                        'all' => 'Ambas',
                                    ])
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Radio $component, ?Setores $record, Set $set): void {
                                        if (! $record) {
                                            return;
                                        }

                                        $funcao = static::resolveFuncaoFromFields($record->comarca, $record->vara);

                                        $component->state($funcao);
                                        static::setFuncaoFields($funcao, $set);
                                    })
                                    ->afterStateUpdated(fn (?string $state, Set $set): null => static::setFuncaoFields($state, $set))
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->columnSpan(8),

                                Toggle::make('ehPresidencia')
                                    ->label('Presidência')
                                    ->default(false)
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Toggle $component, ?Setores $record): void {
                                        if ($record) {
                                            $component->state($record->presidencia === 'Sim');
                                        }
                                    })
                                    ->afterStateUpdated(fn (bool $state, Set $set) => $set('presidencia', $state ? 'Sim' : 'Não'))
                                    ->inline(false)
                                    ->columnSpan(4),

                                Hidden::make('comarca'),
                                Hidden::make('vara'),
                                Hidden::make('presidencia')
                                    ->default('Não'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('codigopai')
                    ->orWhereColumn('id', '<>', 'codigopai'))
                ->with('unidadeOrganizacionalRaiz'))
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('codigopai', 'Código Pai'),
                TableColumns::text('unidadeorganizacional', 'Unidade Organizacional')
                    ->wrap(),
                TableColumns::text('setor', 'Setor')
                    ->wrap(),
                TableColumns::text('updated_at', 'Atualizado em')
                    ->dateTime('d/m/Y')
                    ->description(fn ($record) => $record->updated_at->format('H:i')),
            ])
            ->filters([
                SelectFilter::make('unidade_organizacional_filter')
                    ->label('Unidade Organizacional')
                    ->attribute('codigodauo')
                    ->columnSpan(3)
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Setores::query()
                        ->whereColumn('id', 'codigodauo')
                        ->whereKeyNot(0)
                        ->orderBy('unidadeorganizacional')
                        ->pluck('unidadeorganizacional', 'id')
                        ->toArray()),
            ])
            ->groups([
                Group::make('codigodauo')
                    ->label('Unidade Organizacional')
                    ->titlePrefixedWithLabel(false)
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw('codigodauo IS NULL')
                        ->orderBy('codigodauo', $direction))
                    ->getTitleFromRecordUsing(fn (Setores $record): string => $record->unidadeOrganizacionalRaiz?->unidadeorganizacional
                        ?? (filled($record->codigodauo) ? "Unidade não cadastrada #{$record->codigodauo}" : 'Sem unidade organizacional'))
                    ->getDescriptionFromRecordUsing(fn (Setores $record): ?string => filled($record->codigodauo)
                        ? "Código da UO: {$record->codigodauo}"
                        : null)
                    ->collapsible(),
            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip('Editar')
                    ->modalHeading(' ')
                    ->modalWidth(Width::SixExtraLarge)
                    ->hiddenLabel(),
                ViewAction::make()
                    ->tooltip('Visualizar')
                    ->modalHeading(' ')
                    ->modalWidth(Width::SixExtraLarge)
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
            ])
            ->defaultGroup('codigodauo')
            ->groupingSettingsHidden()
            ->groupingDirectionSettingHidden()
            ->defaultSort('setor');
    }

    public static function normalizeUnidadeOrganizacionalData(array $data): array
    {
        $data = static::normalizeFuncaoData($data);
        $data = static::normalizePresidenciaData($data);
        $unidadeOrganizacionalId = $data['unidade_organizacional_id'] ?? null;

        unset($data['unidade_organizacional_id']);

        if (blank($unidadeOrganizacionalId)) {
            return $data;
        }

        $unidadeOrganizacional = Setores::query()
            ->whereColumn('id', 'codigodauo')
            ->whereKey($unidadeOrganizacionalId)
            ->first();

        if (! $unidadeOrganizacional) {
            return $data;
        }

        $data['codigopai'] = $unidadeOrganizacional->id;
        $data['codigodauo'] = $unidadeOrganizacional->id;
        $data['unidadeorganizacional'] = $unidadeOrganizacional->unidadeorganizacional;

        return $data;
    }

    public static function normalizePresidenciaData(array $data): array
    {
        $data['presidencia'] = (bool) ($data['ehPresidencia'] ?? false) ? 'Sim' : 'Não';

        unset($data['ehPresidencia']);

        return $data;
    }

    public static function normalizeFuncaoData(array $data): array
    {
        $funcao = $data['funcao'] ?? null;

        unset($data['funcao']);

        [$comarca, $vara] = static::resolveFuncaoFields($funcao);

        $data['comarca'] = $comarca;
        $data['vara'] = $vara;

        return $data;
    }

    private static function setFuncaoFields(?string $funcao, Set $set): null
    {
        [$comarca, $vara] = static::resolveFuncaoFields($funcao);

        $set('comarca', $comarca);
        $set('vara', $vara);

        return null;
    }

    private static function resolveFuncaoFields(?string $funcao): array
    {
        return match ($funcao) {
            'V' => [null, 'V'],
            'C' => ['C', null],
            'all' => ['C', 'V'],
            default => [null, null],
        };
    }

    private static function resolveFuncaoFromFields(?string $comarca, ?string $vara): ?string
    {
        return match (true) {
            $comarca === 'C' && $vara === 'V' => 'all',
            $vara === 'V' => 'V',
            $comarca === 'C' => 'C',
            default => null,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSetores::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            SetoresCountStats::class,
        ];
    }
}
