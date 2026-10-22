<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnexosProcessoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('num_processo')
                            ->label('Processo')
                            ->relationship('processoRelacaoRef', 'num_processo')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Select::make('tipo_documento')
                            ->label('Tipo de Documento')
                            ->relationship('tipoDocumentoRelacaoRef', 'descricao')
                            ->searchable()
                            ->preload(),

                        DatePicker::make('data')
                            ->label('Data')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        TextInput::make('num_documento')
                            ->label('Nº Documento')
                            ->maxLength(255),

                        Select::make('material')
                            ->label('Material')
                            ->relationship('materialRelacaoRef', 'Descricao')
                            ->searchable()
                            ->optionsLimit(50),

                        FileUpload::make('anexo_documento')
                            ->label('Anexo')
                            ->disk('egap')
                            ->directory('files/processos')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
