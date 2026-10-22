<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\DescricaoResumidaResource\Pages\ListDescricaoResumidas;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\DescricaoResumida;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class DescricaoResumidaResource extends CadastroResource
{
    protected static ?string $model = DescricaoResumida::class;
    protected static ?int $navigationSort = 3;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Descrição Resumida';

    protected static ?string $pluralModelLabel = 'Descrições Resumidas';

    protected static ?string $modelLabel = 'Descrição Resumida';

    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informações Gerais')
                ->schema([
                    Select::make('id_tipo_material')
                        ->label('Tipo Material')
                        ->required()
                        ->options([
                            'P' => 'Material Permanente',
                            'C' => 'Material de Consumo',
                            'D' => 'Material de Consumo Durável',
                        ])
                        ->columnSpanFull(),

                    TextInput::make('Descricao')
                        ->label('Descrição')
                        ->required()
                        ->columnSpanFull(),

                    Select::make('ContaContabil')
                        ->label('Conta Contábil')
                        ->required()
                        ->relationship(
                            name: 'conta_contabil',
                            titleAttribute: 'titulo'
                        )
                        ->searchable()
                        ->preload(),

                    Select::make('id_produto')
                        ->label('Elemento de Despesa')
                        ->required()
                        ->relationship(
                            name: 'produto_id',
                            titleAttribute: 'DescricaodaClasse'
                        )
                        ->searchable()
                        ->preload(),

                    Select::make('visibilidade')
                        ->options([
                            '0' => 'Ninguém',
                            '1' => 'Comarcas',
                            '2' => 'Tribunal',
                            '3' => 'Todos',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Arquivos')
                ->schema([
                    FileUpload::make('imagem')
                        ->image()
                        ->disk('egap')
                        ->directory('images/descricao')
                        ->imagePreviewHeight('150'),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('Descricao', 'Descrição', isFirstColumn: true)
                    ->wrap()
                    ->limit(50),
                TableColumns::text('codigo_da_classe.DescricaodaClasse', 'Classe'),
                TableColumns::text('conta_contabil.titulo', 'Conta Contábil'),
                TableColumns::updatedBy('atualizado_por.name'),
                TableColumns::text('visibilidade')
                    ->getStateUsing(function ($record) {
                        switch ($record->visibilidade) {
                            case 0:
                                return 'Ninguém';
                            case 1:
                                return 'Comarcas';
                            case 2:
                                return 'Tribunal';
                            case 3:
                                return 'Todos';
                            default:
                                return 'Nenhuma';
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDescricaoResumidas::route('/'),
        ];
    }
}
