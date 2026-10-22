<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TermoResponsabilidadeResource\Pages\ListTermoResponsabilidades;
use App\Filament\Egap\Support\LegacyFileUrl;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\TermoResponsabilidade;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TermoResponsabilidadeResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = TermoResponsabilidade::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Termos de Responsabilidade';
    protected static ?string $modelLabel = 'Termo de Responsabilidade';
    protected static ?string $pluralModelLabel = 'Termos de Responsabilidade';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 7;
    protected static ?string $slug = 'bens-imoveis/termo-responsabilidade-imoveis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('num_termo')
                            ->label('Termo Nº'),

                        TextInput::make('ano_termo')
                            ->label('Ano')
                            ->numeric()
                            ->mask('9999'),

                        FileUpload::make('arquivo')
                            ->label('Arquivo')
                            ->disk('egap')
                            ->required()
                            ->directory('files/imoveis/termo-responsabilidade')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'desc')
            ->columns([
                TableColumns::text('num_termo', 'Termo Nº', isFirstColumn: true)
                    ->badge(),
                TableColumns::text('ano_termo', 'Ano'),
                TableColumns::text('arquivo', 'Arquivo')
                    ->url(fn($record): ?string => LegacyFileUrl::resolve($record->arquivo, config('app.egap')))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($record) => $record->arquivo ? 'Abrir' : '-')
                    ->color('primary')
                    ->weight('bold'),
                TableColumns::updatedBy('atualizadoPorRef.name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTermoResponsabilidades::route('/'),
        ];
    }
}
