<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Escola\ProjetoPedagogicoCluster;
use App\Filament\Resources\Escola\AcaoDespesasResource\Pages;
use App\Filament\Resources\AcaoDespesasResource\RelationManagers;
use App\Models\Escola\AcaoDespesas;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcaoDespesasResource extends Resource
{
    protected static ?string $model = AcaoDespesas::class;
    protected static ?string $cluster = ProjetoPedagogicoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Despesas das Ação ';
    protected static ?string $puralModelLabel = 'Despesas das Ação ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Despesas das Ação ';
    // protected static string | UnitEnum | null $navigationGroup = 'Orçamento';
    protected static ?int $navigationSort = 2;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('descricao')
                ->label('Descrição da Despesa')
                ->label('Descrição da Despesa')
                ->columnSpan(4),

            Forms\Components\Select::make('natureza_id')
                ->label('Natureza')
                ->preload()
                ->relationship(
                    name: 'natureza',
                    titleAttribute: 'descricao',
                    modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'NATUREZA_DESPESA'),
                )
                ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->codigo}: {$record->descricao}")
                ->searchable()
                ->columnSpan(4),

            Forms\Components\Select::make('tipo')
                ->label('Tipo')
                ->options([
                    1 => 'Hora Aula',
                    2 => 'Unidade',
                ])
                ->live()
                ->columnSpan(3),

            Forms\Components\TextInput::make('horas_aula')
                ->label('Horas aula')
                ->hidden(fn(Get $get): bool => $get('tipo') != 1)
                ->columnSpan(2),

            Forms\Components\TextInput::make('quantidade')
                ->label('Quantidade')
                ->hidden(fn(Get $get): bool => $get('tipo') != 2)
                ->columnSpan(2),

            Forms\Components\TextInput::make('valor')
                ->label('Valor')
                ->numeric()
                ->prefix('R$')
                ->inputMode('decimal')
                ->columnSpan(2),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormFields()
            )
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('acao.descricao')
                    ->label('Ação')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('natureza.codigo')
                    ->label('Natureza')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn(string $state): string => ($state == 1 ? "Horas aula" : "Unidade"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('horas_aula')
                    ->label('Horas aula')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Quantidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->numeric(2)
                    ->prefix('R$')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('acao')
                    ->relationship(
                        name: 'acao',
                        titleAttribute: 'descricao',
                    )
                    ->searchable(['descricao'])
                    ->preload()
                    ->attribute('acao_id')
                    ->default(request()->get('acao_id'))
                    ->columnSpan(6),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
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
            'index' => Pages\ListAcaoDespesas::route('/'),
            'create' => Pages\CreateAcaoDespesas::route('/create'),
            'edit' => Pages\EditAcaoDespesas::route('/{record}/edit'),
        ];
    }
}
