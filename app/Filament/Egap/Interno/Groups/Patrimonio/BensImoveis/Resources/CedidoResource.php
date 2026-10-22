<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource\Pages\CreateCedido;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource\Pages\EditCedido;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource\Pages\ListCedidos;
use App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Cedido;
use App\Models\egap\Patrimonio\BensImoveis\Processo;
use App\Models\egap\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class CedidoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Cedido::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Imóveis Ocupados por Terceiros';
    protected static ?string $modelLabel = 'Imóvel Ocupado por Terceiro';
    protected static ?string $pluralModelLabel = 'Imóveis Ocupados por Terceiros';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'bens-imoveis/ocupados-por-terceiros';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Imóvel')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Identificação')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Select::make('id_imovel')
                                            ->label('Imóvel')
                                            ->relationship('imovelRelacaoref', 'descricao')
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(8)
                                            ->preload()
                                            ->placeholder('Selecione o imóvel')
                                            ->columnSpanFull(),

                                        Select::make('num_processo')
                                            ->label('Nº do Processo')
                                            ->columnSpan(4)
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->options(fn () => Processo::query()->orderByDesc('date_time')->pluck('num_processo', 'num_processo')->toArray())
                                            ->suffixAction(
                                                CreateAction::make('adicionar_processo')
                                                    ->model(Processo::class)
                                                    ->iconButton()
                                                    ->icon(Heroicon::OutlinedFolderPlus)
                                                    ->modalSubmitActionLabel('Novo')
                                                    ->modalHeading('Criar Processo')
                                                    ->tooltip('Novo')
                                                    ->createAnother(false)
                                                    ->schema(fn (Schema $schema): Schema => ProcessosAdmResource::form($schema))
                                                    ->after(fn (Processo $record, Set $set) => $set('num_processo', $record->num_processo))
                                            ),

                                        FileUpload::make('termo_digital')
                                            ->label('Termo Digital')
                                            ->columnSpanFull()
                                            ->helperText('Anexe o termo ou documento relacionado à ocupação.')
                                            ->disk('egap')
                                            ->directory('files/imoveis/cedidos'),
                                    ])
                                    ->columns(12),

                                Section::make('Dados da Ocupação')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Textarea::make('resumo')
                                            ->label('Partes/Terceiros')
                                            ->placeholder('Informe as partes envolvidas ou terceiros ocupantes')
                                            ->rows(4),

                                        Textarea::make('proprietario_responsavel')
                                            ->label('Proprietário/Responsável')
                                            ->placeholder('Informe o proprietário ou responsável')
                                            ->rows(4),

                                        Textarea::make('condicao_uso')
                                            ->label('Condição de Uso')
                                            ->placeholder('Descreva a condição de uso do espaço')
                                            ->rows(4),

                                        Textarea::make('objeto')
                                            ->label('Objeto')
                                            ->placeholder('Descreva o objeto da cessão ou ocupação')
                                            ->rows(4),

                                        Textarea::make('fiscais')
                                            ->label('Fiscais')
                                            ->placeholder('Informe os fiscais responsáveis')
                                            ->columnSpanFull()
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Situação')
                            ->icon('heroicon-o-check-circle')
                            ->schema([
                                Section::make('Status e Controle')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Select::make('situacao')
                                            ->label('Situação')
                                            ->options([
                                                'Vigente' => 'Vigente',
                                                'Encerrado' => 'Encerrado',
                                            ])
                                            ->columnSpanFull()
                                            ->placeholder('Selecione a situação'),

                                        Textarea::make('observacao')
                                            ->label('Observação')
                                            ->placeholder('Registre informações complementares sobre a situação')
                                            ->columnSpanFull()
                                            ->rows(4),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Publicações')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Assinatura e Publicação')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        DatePicker::make('data_assinatura')
                                            ->label('Data de Assinatura')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),

                                        TextInput::make('ato_diario')
                                            ->label('Ato Diário')
                                            ->placeholder('Informe o ato diário'),

                                        DatePicker::make('data_publicacao')
                                            ->label('Data de Publicação')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Vigência')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Section::make('Prazos')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        DatePicker::make('vencimento')
                                            ->label('Vencimento')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),

                                        TextInput::make('vigencia')
                                            ->label('Vigência')
                                            ->placeholder('Ex: 12 meses'),

                                        DatePicker::make('aditivo_vigencia')
                                            ->label('Aditivo de Vigência')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Ocupação do Espaço')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Condições Financeiras e Despesas')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->schema([
                                        Radio::make('retribuicao')
                                            ->label('Retribuição')
                                            ->options([
                                                'Oneroso' => 'Oneroso',
                                                'Gratuito' => 'Gratuito',
                                            ])
                                            ->columnSpan(1),

                                        CheckboxList::make('despesas')
                                            ->label('Despesas')
                                            ->options([
                                                'Energia' => 'Energia',
                                                'Água' => 'Água',
                                                'Tributos' => 'Tributos',
                                                'taxa de coleta de lixo' => 'taxa de coleta de lixo',
                                            ])
                                            ->columns(2)
                                            ->columnSpan(1),
                                    ])->columns(2)
                            ]),

                        Tab::make('Gestores/Fiscais')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Equipe Responsável')
                                    ->icon('heroicon-o-user-group')
                                    ->schema([
                                        Repeater::make('gestores')
                                            ->relationship('gestores')
                                            ->schema([
                                                Select::make('gestor_fiscal')
                                                    ->label('Perfil')
                                                    ->native(false)
                                                    ->options([
                                                        'Gestor' => 'Gestor',
                                                        'Fiscal' => 'Fiscal',
                                                    ])
                                                    ->placeholder('Selecione'),

                                                Select::make('nome')
                                                    ->label('Nome')
                                                    ->relationship('nomeRelacaoref', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Selecione o responsável'),

                                                TextInput::make('ato_diario')
                                                    ->label('Ato Diário')
                                                    ->placeholder('Informe o ato diário'),

                                                DatePicker::make('data_publicacao')
                                                    ->label('Data de Publicação')
                                                    ->displayFormat('d/m/Y')
                                                    ->native(false),

                                                DatePicker::make('data_encerramento')
                                                    ->label('Data de Encerramento')
                                                    ->displayFormat('d/m/Y')
                                                    ->native(false),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->hiddenLabel()
                                            ->addActionLabel('Adicionar gestor/fiscal'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('imovelRelacaoref.descricao', 'Imóvel', isFirstColumn: true)
                    ->limit(45)
                    ->tooltip(fn($record): ?string => $record->imovelRelacaoref?->descricao),
                TableColumns::text('resumo', 'Partes/Terceiros')
                    ->limit(40)
                    ->tooltip(fn($record): ?string => $record->resumo),
                TableColumns::text('proprietario_responsavel', 'Proprietário/Responsável')
                    ->limit(35)
                    ->tooltip(fn($record): ?string => $record->proprietario_responsavel),
                TableColumns::text('condicao_uso', 'Condição de Uso')
                    ->limit(35)
                    ->tooltip(fn($record): ?string => $record->condicao_uso),
                TableColumns::text('num_processo', 'Nº Processo')
                    ->badge(),
                TableColumns::text('situacao', 'Situação')
                    ->badge(),
                TableColumns::date('data_assinatura', 'Assinatura'),
                TableColumns::date('vencimento', 'Vencimento'),
                TableColumns::text('vigencia', 'Vigência')
                    ->badge(),
                TableColumns::text('retribuicao', 'Retribuição')
                    ->badge(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCedidos::route('/'),
            'create' => CreateCedido::route('/create'),
            'edit' => EditCedido::route('/{record}/edit'),
        ];
    }
}
