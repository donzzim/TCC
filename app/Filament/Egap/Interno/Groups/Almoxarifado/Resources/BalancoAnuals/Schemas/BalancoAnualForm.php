<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnuals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BalancoAnualForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_inicio')
                    ->label('Início')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->native(false),

                DatePicker::make('termino')
                    ->label('Término')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->afterOrEqual('data_inicio'),

                Textarea::make('mensagem')
                    ->label('Mensagem')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }
}
