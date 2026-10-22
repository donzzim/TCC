<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Portal\CMSTemplatesResource\Pages;
use App\Filament\Resources\Portal\CMSemplatesResource\RelationManagers;
use App\Models\Portal\CMSTemplates;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSTemplatesResource extends Resource
{
    protected static ?string $model = CMSTemplates::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Templates ';
    protected static ?string $puralModelLabel = 'Templates ';
    protected static ?string $navigationLabel = 'Templates ';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-templates';
    protected static ?int $navigationSort = 7;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('view')->required(),
            Forms\Components\TextInput::make('css_path')->required(),
            Forms\Components\Toggle::make('is_active')->label('Ativo'),
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
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('view'),
                Tables\Columns\TextColumn::make('css_path'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Ativo'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('activate')
                    ->hiddenLabel()
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (CMSTemplates $record) {
                        // Desativa todos os outros templates para o mesmo setor_id
                        CMSTemplates::where('setor_id', $record->setor_id)
                            ->where('id', '!=', $record->id)
                            ->update(['is_active' => false]);

                        // Ativa o template selecionado
                        $record->update(['is_active' => true]);
                    })
                    // Desabilita o botão se o template já estiver ativo
                    ->disabled(fn(CMSTemplates $record): bool => $record->is_active),
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
            'index' => Pages\ListCMSTemplates::route('/'),
            'create' => Pages\CreateCMSTemplates::route('/create'),
            'edit' => Pages\EditCMSTemplates::route('/{record}/edit'),
        ];
    }
}
