<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Escola\FormSubmission;
use App\Filament\Resources\Escola\SubmissionResource\Pages;
use App\Filament\Resources\SubmissionResource\Widgets\ResultadoAvaliacaoChart;
use App\Models\Escola\CursoAlunos;
use Filament\Support\Enums\Width;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class SubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $recordTitleAttribute = 'form_id';
    protected static ?string $modelLabel = 'Respostas da Avaliação de Reação ';
    protected static ?string $puralModelLabel = 'Respostas da Avaliação ';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?string $navigationLabel = 'Respostas da Avaliação';
    protected static ?int $navigationSort = 10;
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('curso_id')
                ->relationship('curso', 'titulo')
                ->required()
                ->label('Curso'),
            Forms\Components\Select::make('form_id')
                ->relationship('form', 'name')
                ->required()
                ->label('Formulário'),
            Forms\Components\Select::make('user_id')
                ->relationship('usuario', 'name')
                ->required()
                ->label('Usuário'),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('curso.titulo')
                ->label('Curso'),
            Tables\Columns\TextColumn::make('curso.inicio')
                ->label('Início')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('curso.termino')
                ->label('Término')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('form.name')
                ->label('Formulário'),
            Tables\Columns\TextColumn::make('usuario.name')
                ->label('Usuário'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Data')
                ->dateTime('d/m/Y H:i'),
        ])
            ->filters([

                SelectFilter::make('curso')
                    ->relationship(name: 'curso', titleAttribute: 'titulo')
                    ->searchable(['titulo'])
                    ->preload()
                    ->attribute('curso_id')
                    ->default(request()->get('curso_id'))
                    ->columnSpan(6),

                SelectFilter::make('form')
                    ->relationship(
                        name: 'form',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    ->preload()
                    ->attribute('form_id')
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
            ->actions([

                Action::make('inscricao')
                    ->hiddenLabel()
                    ->size('xl')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->tooltip('Formulário de Avaliação de Reação')
                    ->url(function ($record) {
                        $aluno = CursoAlunos::where('aluno_id', $record->user_id)
                            ->where('curso_id', $record->curso_id)
                            ->first();
                        $form = $record->form;

                        if ($aluno && $form) {
                            return route('forms.show', ['slug' => $form->slug, 'uuid' => $aluno->uuid]);
                        }
                        return '#';
                    })
                    ->openUrlInNewTab(),

                Action::make('Ver Respostas')
                    ->hiddenLabel()
                    ->size('xl')
                    ->color('success')
                    ->tooltip('Respostas da Avaliação de Reação')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => route('filament.admin.resources.escola.submissions.view', $record))
                    // ->openUrlInNewTab()
                    ->label('Ver Respostas'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'create' => Pages\CreateSubmission::route('/create'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
            'view' => Pages\ViewSubmission::route('/{record}'),
        ];
    }

    // public static function getHeaderWidgets(): array
    // {
    //     return [
    //     ];
    // }
}
