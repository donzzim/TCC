<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Portal\CMSSiteSettingsResource\Pages;
use App\Filament\Resources\Portal\CMSSiteSettingsResource\RelationManagers;
use App\Models\Portal\CMSSiteSettings;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSSiteSettingsResource extends Resource
{
    protected static ?string $model = CMSSiteSettings::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Site';
    protected static ?string $pluralModelLabel = 'Site';
    protected static ?string $navigationLabel = 'Site';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-site-settings';
    protected static ?int $navigationSort = 1;

    public static function getFormfields(): array
    {
        return [
            Section::make('Identidade Visual')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome do Site (interno)')
                        ->required(),
                    Forms\Components\TextInput::make('brand_name')
                        ->label('Nome da Marca (público)')
                        ->required(),
                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Logo Principal')
                        ->image()->directory('site')->required(),
                    Forms\Components\FileUpload::make('favicon_path')
                        ->label('Favicon')
                        ->image()->directory('site')->required(),
                ]),

            Section::make('Contato e Localização')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('phone')->label('Telefone'),
                    Forms\Components\TextInput::make('email')->email()->label('E-mail'),
                    Forms\Components\Textarea::make('address')->label('Endereço')->columnSpanFull(),
                    Forms\Components\Textarea::make('maps_iframe')->label('iFrame do Google Maps')->columnSpanFull(),
                ]),

            Section::make('Redes Sociais')
                ->schema([
                    Forms\Components\Repeater::make('social_links')
                        ->label('Links das Redes Sociais')
                        ->schema([
                            Forms\Components\TextInput::make('network')->label('Rede (ex: Facebook)')->required(),
                            Forms\Components\TextInput::make('url')->label('URL')->url()->required(),
                        ])
                        ->columns(2)
                ]),

            Section::make('Cores do Tema')
                ->columns(3)
                ->schema([
                    Forms\Components\ColorPicker::make('primary_color')->label('Cor Primária'),
                    Forms\Components\ColorPicker::make('secondary_color')->label('Cor Secundária'),
                    Forms\Components\ColorPicker::make('tertiary_color')->label('Cor Terciária'),
                ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')->label('Logo'),
                Tables\Columns\TextColumn::make('name')->label('Nome do Site')->searchable(),
                Tables\Columns\TextColumn::make('brand_name')->label('Nome da Marca')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-mail'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel(),
                ViewAction::make()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->hiddenLabel(),
            ])
            ->toolBarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
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
            'index' => Pages\ListCMSSiteSettings::route('/'),
            'create' => Pages\CreateCMSSiteSettings::route('/create'),
            'edit' => Pages\EditCMSSiteSettings::route('/{record}/edit'),
        ];
    }
}
