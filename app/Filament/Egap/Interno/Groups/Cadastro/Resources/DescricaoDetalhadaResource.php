<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\DescricaoDetalhadaResource\Pages\ListDescricaoDetalhadas;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class DescricaoDetalhadaResource extends CadastroResource
{
    protected static ?string $model = DescricaoDetalhada::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationLabel = 'Descrição Detalhada';

    protected static ?string $pluralModelLabel = 'Descrições Detalhadas';

    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informações Gerais')
                ->schema([
                    Select::make('descricao_resumida')
                        ->label('Descrição Resumida')
                        ->relationship('descricao_resumida_text', 'Descricao'),

                    Select::make('marca')
                        ->label('Marca')
                        ->relationship('marca_text', 'descricao'),

                    Select::make('modelo')
                        ->label('Modelo')
                        ->relationship('modelo_text', 'descricao'),

                    Textarea::make('descricao_detalhada')
                        ->label('Descrição Detalhada')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('valor_mercado')
                        ->label('Valor de Mercado')
                        ->numeric()
                        ->prefix('R$')
                        ->step('0.01')
                        ->columnSpan(2),

                    Select::make('visibilidade')
                        ->options([
                            '0' => 'Ninguém',
                            '1' => 'Comarcas',
                            '2' => 'Tribunal',
                            '3' => 'Todos',
                        ])
                        ->native(false)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make('Arquivos')
                ->schema([
                    FileUpload::make('imagem')
                        ->image()
                        ->disk('egap')
                        ->directory('images/descricoes')
                        ->imagePreviewHeight('150'),

                    FileUpload::make('pdf')
                        ->label('PDF')
                        ->disk('egap')
                        ->directory('files/descricoes')
                        ->acceptedFileTypes(['application/pdf']),
                ])
                ->columns(2)
                ->columnSpanFull(),

        ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('descricao_resumida_text.Descricao', 'Descrição Resumida', isFirstColumn: true)
                    ->wrap(),
                TableColumns::text('descricao_detalhada', 'Descrição Detalhada')
                    ->wrap(),
                TableColumns::text('marca_text.descricao', 'Marca')
                    ->wrap(),
                TableColumns::text('modelo_text.descricao', 'Modelo')
                    ->wrap(),
                TableColumns::money('valor_mercado'),
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
                TableColumns::updatedBy('atualizado_por_usuario.name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn($record) => "Editar Descrição Detalhada #{$record->id}")
                    ->tooltip('Editar')
                    ->hiddenLabel(),
                ViewAction::make()
                    ->tooltip('Visualizar')
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDescricaoDetalhadas::route('/'),
        ];
    }
}
