<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjetoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Projeto')
                    ->description('Identificação do projeto e seu vínculo com ação, plano orçamentário e unidade judiciária.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->schema([
                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('acao')
                            ->label('Ação')
                            ->relationship('acaoRef', 'descricao')
                            ->searchable()
                            ->preload(),

                        Select::make('plano_orcamentario')
                            ->label('Plano Orçamentário')
                            ->relationship('planoOrcamentarioRef', 'nome')
                            ->searchable()
                            ->preload(),

                        Select::make('unidade_judiciaria')
                            ->label('Unidade Judiciária')
                            ->relationship('unidadeJudiciariaRef', 'Setor')
                            ->searchable()
                            ->preload(),

                        TextInput::make('area')
                            ->label('Área')
                            ->numeric(),
                    ])
                    ->columns(2),
            ]);
    }
}
