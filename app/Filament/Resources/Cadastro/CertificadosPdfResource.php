<?php

namespace App\Filament\Resources\Cadastro;

use App\Filament\Resources\Cadastro\CertificadosPdfResource\Pages;
use App\Models\CertificadosPdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CertificadosPdfResource extends Resource
{
    protected static ?string $model = CertificadosPdf::class;

    protected static ?string $recordTitleAttribute = 'participante';

    protected static ?string $modelLabel = 'Certificado PDF';

    protected static ?string $pluralModelLabel = 'Certificados PDF';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Certificados PDF';

    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo_evento')
                    ->label('Curso')
                    ->description(fn (CertificadosPdf $record): string => 'Carga horária: ' . $record->carga_horaria . ' horas')
                    ->wrap()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->label('Período')
                    ->searchable()
                    ->color('info')
                    ->badge()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instrutores')
                    ->label('Instrutores')
                    ->searchable()
                    ->toggleable()
                    ->badge()
                    ->separator(';'),
                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('participante')
                    ->label('Aluno')
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('url_origem')
                    ->hiddenLabel()
                    ->label('Certificados dos alunos')
                    ->tooltip('Certificados dos alunos')
                    ->size('xl')
                    ->icon('heroicon-o-document')
                    ->url(fn (CertificadosPdf $record): ?string => $record->url_origem)
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListCertificadosPdfs::route('/'),
        ];
    }
}
