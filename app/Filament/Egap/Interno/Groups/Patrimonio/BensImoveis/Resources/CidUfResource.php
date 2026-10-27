<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource\Pages\ListCidUfs;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\CidUf;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CidUfResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = CidUf::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Cidade/UF';
    protected static ?string $modelLabel = 'Cidade/UF';
    protected static ?string $pluralModelLabel = 'Cidades/UF';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 9;
    protected static ?string $slug = 'bens-imoveis/cidades-uf';

    private const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
        'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Select::make('id_cidade')
                    ->label('Cidade')
                    ->relationship('idCidadeRef', 'descricao')
                    ->searchable()
                    ->native(false)
                    ->optionsLimit(50)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Esta cidade já tem UF e CEP cadastrados.'])
                    ->columnSpan(2)
                    ->required(),

                Select::make('cd_uf')
                    ->label('UF')
                    ->options(array_combine(self::UFS, self::UFS))
                    ->default('ES')
                    ->searchable()
                    ->native(false)
                    ->columnSpan(1)
                    ->required(),

                TextInput::make('cd_cep_cidade')
                    ->label('CEP')
                    ->mask('99999-999')
                    ->placeholder('00000-000')
                    ->regex('/^\d{5}-?\d{3}$/')
                    ->validationMessages(['regex' => 'Informe o CEP com 8 dígitos.'])
                    ->formatStateUsing(fn ($state): ?string => self::cep($state))
                    ->dehydrateStateUsing(fn (?string $state): int => (int) preg_replace('/\D/', '', (string) $state))
                    ->columnSpan(1)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('idCidadeRef'))
            ->defaultSort('idCidadeRef.descricao')
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true)
                    ->alignLeft(),
                TableColumns::text('idCidadeRef.descricao', 'Cidade')
                    ->alignLeft(),
                TableColumns::text('cd_uf', 'UF'),
                TableColumns::text('cd_cep_cidade', 'CEP')
                    ->formatStateUsing(fn ($state): string => self::cep($state) ?? '-'),
            ])
            ->filters([
                SelectFilter::make('cd_uf')
                    ->label('UF')
                    ->options(array_combine(self::UFS, self::UFS)),
            ]);
    }

    /** 29687000 → "29687-000"; 0 (sem CEP na base antiga) → null. */
    private static function cep($valor): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);

        if ((int) $digitos === 0) {
            return null;
        }

        $digitos = str_pad($digitos, 8, '0', STR_PAD_LEFT);

        return substr($digitos, 0, 5) . '-' . substr($digitos, 5);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCidUfs::route('/'),
        ];
    }
}
