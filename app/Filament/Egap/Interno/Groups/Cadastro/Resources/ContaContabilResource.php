<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ContaContabilResource\Pages\ListContaContabils;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\ContaContabil;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class ContaContabilResource extends CadastroResource
{
    protected static ?string $model = ContaContabil::class;
    protected static ?int $navigationSort = 2;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Conta Contábil';
    protected static ?string $modelLabel = 'Conta Contábil';
    protected static ?string $pluralModelLabel = 'Contas Contábeis';
    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?string $maxContentWidth = '3xl';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->mask('*.*.*.*.*.**.**')
                            ->maxLength(15)
                            ->columnSpan(1),

                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Textarea::make('funcao')
                            ->label('Função')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('codigo', 'Código', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('titulo', 'Título')
                    ->limit(50),
                TableColumns::text('funcao', 'Função')
                    ->wrap(),
                TableColumns::updatedBy('atualizado_por.name'),
            ])
            ->defaultSort('codigo');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContaContabils::route('/'),
        ];
    }
}
