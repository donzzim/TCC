<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PlanoOrcamentarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Plano Orçamentário')
                    ->description('Identificação e setor responsável pelo plano.')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Select::make('setor_responsavel')
                            ->label('Setor Responsável')
                            ->relationship('setorResponsavelRef', 'Setor')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
