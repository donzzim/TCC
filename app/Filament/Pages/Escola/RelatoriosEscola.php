<?php

namespace App\Filament\Pages\Escola;

use UnitEnum;
use BackedEnum;
use App\Models\Escola\Cursos; // Seu Model
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Route;

class RelatoriosEscola extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.escola.relatorios-escola';

    protected static ?string $recordTitleAttribute = 'Relatórios';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $puralModelLabel = 'Relatórios';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getReportTypeOptions(): array
    {
        return [
            'cursos_ativos' => 'Cursos Ativos',
            'cursos_por_periodo' => 'Cursos por Período',
            'cursos_por_periodo_capacitados_mag_serv' => 'Cursos por Período, Capacitados, Magistrados e Servidores',
            'cursos_por_titulo' => 'Cursos pelo Título',
            'cursos_por_tematica' => 'Cursos por Temática',
            'cursos_inscritos-cargo-setor' => 'Cursos por Alunos Inscritos, Cargo e Setor',
            'cursos_aprovados-cargo-setor-simplificado' => 'Cursos por Alunos Aprovados, Cargo e Setor (simplificado)',
            'cursos_aprovados-cargo-setor-detalhado' => 'Cursos por Alunos Aprovados, Cargo e Setor (detalhado)',
            'avaliacao_reacao_quantitativo' => 'Avaliação de Reação - Quantitativo / Anônimo',
            'avaliacao_reacao_descritivo_aprovados' => 'Avaliação de Reação - Descritivo por Aluno Aprovado',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuração do Relatório')
                    ->schema([
                        // 1. Seleção do Relatório
                        Select::make('report_type')
                            ->label('Selecione o Relatório')
                            ->options(self::getReportTypeOptions())
                            ->required()
                            ->live() // Importante: torna o form reativo
                            ->afterStateUpdated(fn($state) => $this->validate()),

                        Grid::make(2)
                            ->schema([
                                // Filtro: Período (Aparece se o relatório exigir data)
                                DatePicker::make('data_inicio')
                                    ->label('Data Início')
                                    ->visible(fn(Get $get) => in_array($get('report_type'), [
                                        'cursos_por_periodo',
                                        'cursos_por_periodo_capacitados_mag_serv',
                                        'cursos_ativos',
                                        'avaliacao_reacao_quantitativo',
                                        'avaliacao_reacao_descritivo_aprovados',
                                    ])),

                                DatePicker::make('data_fim')
                                    ->label('Data Término')
                                    ->visible(fn(Get $get) => in_array($get('report_type'), [
                                        'cursos_por_periodo',
                                        'cursos_por_periodo_capacitados_mag_serv',
                                        'cursos_ativos',
                                        'avaliacao_reacao_quantitativo',
                                        'avaliacao_reacao_descritivo_aprovados',
                                    ])),

                                // Filtro: Seleção de Cursos
                                Select::make('cursos_ids')
                                    ->label('Cursos Específicos')
                                    ->multiple()
                                    ->options(Cursos::pluck('titulo', 'id'))
                                    ->searchable()
                                    ->visible(fn(Get $get) => in_array(
                                        $get('report_type'),
                                        [
                                            'cursos_por_titulo',
                                            'cursos_aprovados-cargo-setor-simplificado',
                                            'cursos_aprovados-cargo-setor-detalhado',
                                            'cursos_inscritos-cargo-setor',
                                            'avaliacao_reacao_quantitativo',
                                            'avaliacao_reacao_descritivo_aprovados',
                                        ]
                                    )),

                                // Filtro: Ativo
                                Toggle::make('ativo')
                                    ->label('Somente Ativos')
                                    ->default(true)
                                    ->visible(fn(Get $get) => $get('report_type') === 'cursos_por_titulo'),

                                // Exemplo de Temáticas (caso tenha model Tematica)
                                // Select::make('tematica_id')... visible se 'cursos_por_tematica'
                            ]),
                    ])->columns(1),
            ])
            ->statePath('data');
    }
}
