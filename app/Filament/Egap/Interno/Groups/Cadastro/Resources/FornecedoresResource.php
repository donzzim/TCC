<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Interno\Groups\Cadastro\Resources\FornecedoresResource\Pages\ListFornecedores;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\Fornecedores;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FornecedoresResource extends CadastroResource
{
    protected static ?string $model = Fornecedores::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Fornecedores';
    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?string $modelLabel = 'Fornecedor';
    protected static ?string $pluralModelLabel = 'Fornecedores';
    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        TextInput::make('NomeFornecedor')
                            ->label('Nome do Fornecedor')
                            ->placeholder('Ex.: Empresa Modelo Ltda')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),

                        Select::make('Pessoa')
                            ->label('Tipo de Pessoa')
                            ->placeholder('Selecione o tipo')
                            ->native(false)
                            ->searchable(false)
                            ->live()
                            ->options([
                                'Jurídica' => 'Jurídica',
                                'Física' => 'Física',
                            ])
                            ->afterStateUpdated(fn(Set $set) => $set('CNPJ', null))
                            ->required()
                            ->columnSpan(4),

                        TextInput::make('CNPJ')
                            ->label(fn(Get $get): string => static::isPessoaFisica($get('Pessoa')) ? 'CPF' : 'CNPJ')
                            ->placeholder(fn(Get $get): string => static::isPessoaFisica($get('Pessoa')) ? '000.000.000-00' : '00.000.000/0000-00')
                            ->mask(fn(Get $get): string => static::isPessoaFisica($get('Pessoa')) ? '999.999.999-99' : '99.999.999/9999-99')
                            ->stripCharacters(['.', '/', '-'])
                            ->rule(fn(Get $get): string => static::isPessoaFisica($get('Pessoa')) ? 'digits:11' : 'digits:14')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->columnSpan(4),

                        TextInput::make('telefone_comercial')
                            ->label('Telefone Comercial')
                            ->tel()
                            ->placeholder('(00) 0000-0000')
                            ->mask('(99) 9999-9999')
                            ->stripCharacters(['(', ')', ' ', '-'])
                            ->rule('digits:10', fn(?string $state): bool => filled($state))
                            ->columnSpan(4),

                        TextInput::make('telefone_celular')
                            ->label('Telefone Celular')
                            ->tel()
                            ->placeholder('(00) 00000-0000')
                            ->mask('(99) 99999-9999')
                            ->stripCharacters(['(', ')', ' ', '-'])
                            ->rule('digits:11', fn(?string $state): bool => filled($state))
                            ->columnSpan(4),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('NomeFornecedor', 'Fornecedor', isFirstColumn: true)
                    ->wrap(),

                TableColumns::text('Pessoa', 'Tipo')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Jurídica' => 'info',
                        'Física' => 'success',
                        default => 'gray',
                    }),

                TableColumns::text('CNPJ', 'CNPJ / CPF')
                    ->formatStateUsing(fn(?string $state): string => static::formatCnpj($state))
                    ->copyable()
                    ->copyMessage('Documento copiado'),

                TableColumns::updatedBy('atualizado_por.name'),
            ])
            ->filters([
                SelectFilter::make('Pessoa')
                    ->columnSpan(2)
                    ->label('Tipo de Pessoa')
                    ->options([
                        'Jurídica' => 'Jurídica',
                        'Física' => 'Física',
                    ]),
            ])
            ->defaultSort('NomeFornecedor');
    }

    public static function isPessoaFisica(?string $pessoa): bool
    {
        return $pessoa === 'Física';
    }

    public static function formatCnpj(?string $value): string
    {
        $digits = preg_replace('/\D/', '', $value ?? '');

        return match (strlen($digits)) {
            14 => preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digits),
            11 => preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits),
            default => $value ?: '-',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFornecedores::route('/'),
        ];
    }
}
