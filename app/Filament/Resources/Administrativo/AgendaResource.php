<?php

namespace App\Filament\Resources\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use App\Filament\Resources\Administrativo\AgendaResource\Pages;
use App\Filament\Resources\AgendaResource\RelationManagers;
use App\Models\Administrativo\Agenda;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Agenda ';
    protected static ?string $puralModelLabel = 'Agenda ';
    protected static ?string $navigationLabel = 'Agenda ';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\DateTimePicker::make('inicio')
                    ->label('Data Início')
                    ->columnSpan(1),

                Forms\Components\DateTimePicker::make('termino')
                    ->label('Data término')
                    ->columnSpan(1),

                Forms\Components\Toggle::make('dia_todo')
                    ->label('Dia todo')
                    ->default(false)
                    ->columnSpan(1),

                Forms\Components\ColorPicker::make('cor_texto')
                    ->label('Cor do texto')
                    ->columnSpan(1),

                Forms\Components\ColorPicker::make('cor_fundo')
                    ->label('Cod do fundo')
                    ->columnSpan(1),

                Forms\Components\RichEditor::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('inicio')
                    ->label('Data início')
                    ->date('d/m/Y H:i:s')
                    ->searchable(),
                Tables\Columns\TextColumn::make('termino')
                    ->label('Data término')
                    ->date('d/m/Y H:i:s')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('cor_texto')
                    ->label('Cor do texto'),
                Tables\Columns\ColorColumn::make('cor_fundo')
                    ->label('Cor do fundo'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),

                // Action::make('view')
                //     ->hiddenLabel()
                //     ->size('xl')
                //     ->icon('heroicon-o-calendar')
                //     ->url(fn(Agenda $record): string => route('filament.admin.resources.administrativo.agendas.view', $record))
                //     ->openUrlInNewTab(),
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
            'index' => Pages\ListAgendas::route('/'),
            'create' => Pages\CreateAgenda::route('/create'),
            'edit' => Pages\EditAgenda::route('/{record}/edit'),
            // 'view' => Pages\ViewAgenda::route('/{record}'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return new HtmlString("Título: {$record->titulo}<br/>{$record->descricao}");
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['titulo', 'descricao'];
    }
}
