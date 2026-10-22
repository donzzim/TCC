<?php

namespace App\Filament\Resources\Administrativo;

use BackedEnum;
use App\Filament\Resources\Administrativo\LocalResource\Pages;
use App\Models\Escola\TipoLocal;
use App\Services\Geolocalizacao\GeocodingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LocalResource extends Resource
{
    protected static ?string $model = TipoLocal::class;
    protected static ?string $slug = 'administrativo/locais';
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Local';
    protected static ?string $pluralModelLabel = 'Locais';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Locais';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 2;

    public static function getFormfields(): array
    {
        return [
            Section::make('Identificação')
                ->schema([

                    Forms\Components\TextInput::make('descricao')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('json.capacidade')
                        ->label('Capacidade')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('json.disponivel')
                        ->label('Disponível')
                        ->inline(false)
                        ->columnSpan(1),
                    Forms\Components\Toggle::make('json.remoto')
                        ->label('Remoto')
                        ->inline(false)
                        ->columnSpan(1),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make('Localização')
                ->description('Coordenadas obtidas via OpenStreetMap/Nominatim quando disponiveis.')
                ->schema([
                    Forms\Components\Textarea::make('json.endereco_completo')
                        ->label('Endereço completo')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('json.cep')
                        ->label('CEP')
                        ->mask('99999-999')
                        ->maxLength(9)
                        ->suffixAction(
                            Action::make('buscarCep')
                                ->icon('heroicon-m-magnifying-glass')
                                ->tooltip('Buscar CEP')
                                ->requiresConfirmation(false)
                                ->action(fn(Set $set, Get $get) => static::preencherLocalizacaoPorCep($set, $get))
                        )
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('json.latitude')
                        ->label('Latitude')
                        ->numeric()
                        ->minValue(-90)
                        ->disabled()
                        ->dehydrated(true)
                        ->maxValue(90)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('json.longitude')
                        ->label('Longitude')
                        ->numeric()
                        ->minValue(-180)
                        ->maxValue(180)
                        ->disabled()
                        ->dehydrated(true)
                        ->columnSpan(1),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ];
    }

    protected static function preencherLocalizacaoPorCep(Set $set, Get $get): void
    {
        $cep = preg_replace('/\D/', '', (string) $get('json.cep'));

        if (strlen($cep) !== 8) {
            Notification::make()
                ->title('Informe um CEP valido')
                ->danger()
                ->send();

            return;
        }

        $localizacao = GeocodingService::buscarLocalizacaoPorCep($cep);

        if (! $localizacao) {
            Notification::make()
                ->title('CEP nao encontrado')
                ->danger()
                ->send();

            return;
        }

        $latitude = $localizacao['latitude'] ?? null;
        $longitude = $localizacao['longitude'] ?? null;

        $set('json.cep', $localizacao['cep'] ?? GeocodingService::formatarCep($cep));
        $set('json.endereco_completo', $localizacao['endereco_completo'] ?? null);
        $set('json.latitude', $latitude);
        $set('json.longitude', $longitude);

        $notification = Notification::make()
            ->title($latitude && $longitude ? 'Localizacao preenchida' : 'Endereco preenchido');

        if ($latitude && $longitude) {
            $notification->success();
        } else {
            $notification
                ->body('Latitude e longitude nao foram encontradas para este CEP.')
                ->warning();
        }

        $notification->send();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormfields())
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('json.capacidade')
                    ->label('Capacidade'),

                Tables\Columns\ToggleColumn::make('json.disponivel')
                    ->label('Disponível'),

                Tables\Columns\ToggleColumn::make('json.remoto')
                    ->label('Remoto'),

                Tables\Columns\TextColumn::make('json.cep')
                    ->label('CEP')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('json.endereco_completo')
                    ->label('Endereço completo')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('json.latitude')
                    ->label('Latitude')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('json.longitude')
                    ->label('Longitude')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('editar'),
            ])
            ->defaultSort('descricao')
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocais::route('/'),
            'edit' => Pages\EditLocal::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->descricao;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'codigo',
            'descricao',
        ];
    }
}
