<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources;

use App\Filament\Egap\Interno\Groups\Processo\Resources\MateriaisResource\Pages\ListMateriais;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Patrimonio\BensImoveis\Processo;
use App\Models\egap\Processo\ProMaterial;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Processo\ProcessoResource;

class MateriaisResource extends ProcessoResource
{
    protected static ?string $model = ProMaterial::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Processos';

    protected static ?string $navigationLabel = 'Materiais/Serviços';

    protected static ?string $modelLabel = 'Material do Processo';

    protected static ?string $pluralModelLabel = 'Materiais do Processo';

    protected static ?string $slug = 'processos/materiais';

    protected static ?string $recordTitleAttribute = 'lote';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vínculo e rastreabilidade')
                    ->description('Associe o item ao processo e ao material cadastrado.')
                    ->schema([
                        Select::make('processo')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->label('Processo')
                            ->options(fn () : array => Processo::query()->pluck('num_processo', 'id')->toArray()),

                        Select::make('material')
                            ->label('Material')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->options(fn (): array => DescricaoDetalhada::query()
                                ->pluck('descricao_detalhada', 'id')
                                ->toArray()),
                    ])
                    ->columnSpanFull(),

                Section::make('Controle do material')
                    ->description('Organize faixa de estoque, preço de referência e identificação do lote.')
                    ->schema([
                        TextInput::make('lote')
                            ->label('Lote')
                            ->maxLength(255)
                            ->columnSpan(4),

                        MoneyInput::make('preco')
                            ->label('Preço')
                            ->columnSpan(4),

                        MoneyInput::make('saldo_atual')
                            ->label('Saldo atual')
                            ->live(onBlur: true)
                            ->columnSpan(4),

                        TextInput::make('qtde_min')
                            ->label('Quantidade mínima')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->columnSpan(6),

                        TextInput::make('qtde_max')
                            ->label('Quantidade máxima')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->columnSpan(6),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('materialRelacaoRef.descricao_detalhada', 'Material', true),
                TableColumns::number('qtde_min', 'Quantidade Mínima')
                    ->badge(),
                TableColumns::number('qtde_max', 'Quantidade Máxima')
                    ->badge(),
                TableColumns::money('preco', 'Preço'),
                TableColumns::money('saldo_atual', 'Saldo Atual'),
                TableColumns::updatedBy('atualizadoPorRelacaoRef.name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMateriais::route('/'),
        ];
    }

}
