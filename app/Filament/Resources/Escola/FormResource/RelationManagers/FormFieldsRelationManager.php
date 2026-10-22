<?php

namespace App\Filament\Resources\Escola\FormResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('label')->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'text' => 'Texto',
                    'textarea' => 'Área de Texto',
                    'select' => 'Seleção',
                    'select-multiple' => 'Seleção muitos',
                    'radio' => 'Rádio Button',
                    'checkbox' => 'Checkbox',
                ])
                ->required(),
            Forms\Components\Textarea::make('options')
                ->label('Opções')
                ->visible(fn($get) => $get('type') === 'select')
                ->helperText('Uma opção por linha'),
            Forms\Components\Toggle::make('required')->label('Obrigatório'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('label'),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\IconColumn::make('required')->boolean(),
        ]);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['type'] === 'select' && isset($data['options'])) {
            $data['options'] = array_filter(array_map('trim', explode(PHP_EOL, $data['options'])));
        } else {
            $data['options'] = null;
        }

        return $data;
    }
}
