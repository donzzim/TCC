<?php

namespace App\Filament\Actions\Tables;


use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;
use BackedEnum;
use App\Filament\Traits\HasCursoEstatisticas;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

class ViewCursoStatAction extends Action
{
    use HasCursoEstatisticas; // Usa a trait criada no passo 1

    public static function getDefaultName(): ?string
    {
        return 'viewCursoStat';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Estatísticas')
            ->icon('heroicon-o-chart-pie')
            ->tooltip('Visualizar Estatísticas e Detalhes')
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->color('info') // Cor do botão

            // Define o cabeçalho dinâmico do Modal
            ->modalHeading(fn(Model $record) => $record->titulo ?? 'Estatísticas')

            // Define o formulário do Modal
            ->schema([
                // 1. Dados Gerais (Resumo)
                Section::make('Informações Gerais')
                    ->schema([
                        Grid::make(4)->schema([
                            Placeholder::make('inicio_formatado')
                                ->label('Início')
                                ->content(fn($record) => $record->inicio ? $record->inicio->format('d/m/Y H:i') : '-'),

                            Placeholder::make('termino_formatado')
                                ->label('Término')
                                ->content(fn($record) => $record->termino ? $record->termino->format('d/m/Y H:i') : '-'),

                            Placeholder::make('local')
                                ->label('Local')
                                ->content(fn($record) => $record->tipoLocal->descricao ?? 'A definir')
                                ->columnSpan(2),
                        ])
                    ]),

                // 2. Estatísticas (Vindo da Trait)
                ...$this->getEstatisticasSchema(),
            ])

            // Preenche os dados estatísticos ao abrir o modal
            ->mountUsing(function ($form, Model $record) {
                // Carrega relacionamentos se necessário (opcional, mas recomendado para performance)
                $record->loadMissing(['alunos', 'frequencias', 'certificados']);

                // Calcula os dados usando a Trait
                $stats = $this->getEstatisticasData($record);

                // Preenche o formulário
                $form->fill($stats);
            });
    }
}
