<?php

namespace App\Filament\Resources\Cadastro;

use App\Filament\Resources\Cadastro\CursoNettermResource\Pages;
use App\Models\CursosNetterm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CursoNettermResource extends Resource
{
    protected static ?string $model = CursosNetterm::class;

    protected static ?string $recordTitleAttribute = 'nome';

    protected static ?string $modelLabel = 'Curso Netterm';

    protected static ?string $pluralModelLabel = 'Cursos Netterm';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Cursos Netterm';

    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Curso')
                    ->description(fn (CursosNetterm $record): string => 'Carga horária: ' . $record->qtde_horas . ' horas')
                    ->wrap()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Data de início')
                    ->date('d/m/Y')
                    ->color('info')
                    ->badge()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_termino')
                    ->label('Data de término')
                    ->date('d/m/Y')
                    ->color('info')
                    ->badge()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instrutor')
                    ->label('Instrutores')
                    ->searchable()
                    ->toggleable()
                    ->badge(),
                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable()
                    ->toggleable()
                    ->badge(),
                Tables\Columns\TextColumn::make('nome')
                    ->label('Aluno')
                    ->searchable()
                    ->toggleable()
                    ->badge(),
            ])
            ->persistFiltersInSession()
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCursoNetterms::route('/'),
        ];
    }
}
