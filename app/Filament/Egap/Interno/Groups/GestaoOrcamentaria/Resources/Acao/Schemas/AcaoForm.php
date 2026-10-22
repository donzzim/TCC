<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AcaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Ação')
                    ->description('Identificação da ação orçamentária.')
                    ->icon(Heroicon::OutlinedFlag)
                    ->schema([
                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
