<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Escola\FormAvaliacao;
use App\Filament\Resources\Escola\FormResource\Pages;
use Filament\Support\Exceptions\Halt;

class FormResource extends Resource
{
    protected static ?string $model = FormAvaliacao::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Formulário de Avaliação ';
    protected static ?string $puralModelLabel = 'Formulário de Avaliação ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?string $navigationLabel = 'Formulário de Avaliação';
    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->label('Nome do Formulário'),
            Forms\Components\Toggle::make('auth_required')
                ->label('Somente usuários autenticados'),
            Forms\Components\Repeater::make('fields')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->label('Nome do Campo')
                        ->columnSpan(2),

                    Forms\Components\Select::make('type')
                        ->options([
                            'text' => 'Texto',
                            'textarea' => 'Área de Texto',
                            'select' => 'Selecionar',
                            'select-multiple' => 'Selecionar muitos',
                            'radio' => 'Rádio Button',
                            'checkbox' => 'Checkbox',
                        ])
                        ->live()
                        ->required()
                        ->label('Tipo')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('options')
                        ->label('Opções')
                        ->helperText('Opção separados por pipe "|"')
                        ->visible(fn($get) => ($get('type') === 'select' || $get('type') === 'radio' || $get('type') === 'checkbox'))
                        ->columnSpan(3),

                    Forms\Components\Toggle::make('required')
                        ->label('Obrigatório')
                        ->columnSpan(1),

                    // Forms\Components\TextInput::make('order')
                    //     ->numeric()
                    //     ->label('Ordem')
                    //     ->columnSpan(1),
                ])
                ->defaultItems(0)
                ->columns(8)
                ->columnSpanFull()
                ->reorderableWithButtons()
                ->collapsible()
                ->cloneable()
                ->orderColumn('order')
                ->label('Campos do Formuário')
                ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                    $options = [];
                    if (isset($data['options'])) {
                        if (!is_array($data['options'])) {
                            $options = explode('|', $data['options']);
                        } else {
                            $options = $data['options'];
                        }
                    }
                    $data['options'] = $options;
                    return $data;
                })
                ->addActionLabel('Adicionar campo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Título'),
            Tables\Columns\TextColumn::make('slug'),
            Tables\Columns\IconColumn::make('auth_required')
                ->label('Usuários Registrados?')
                ->boolean(),
            Tables\Columns\ToggleColumn::make('ativo'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Criado em')
                ->dateTime('d/m/Y H:i:s'),
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
            ])
            ->toolBarActions([
                DeleteBulkAction::make()
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
            // FormFieldsRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForms::route('/'),
            'create' => Pages\CreateForm::route('/create'),
            'edit' => Pages\EditForm::route('/{record}/edit'),
        ];
    }
}
