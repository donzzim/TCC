<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Components;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\SolicitacaoResource;
use App\Filament\Egap\Support\SetorSelecionado;
use App\Models\egap\Agendamento\Regiao;
use App\Models\egap\Agendamento\Solicitacao;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class SolicitacaoVeiculoForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    protected const DIAS_UTEIS_ANTECEDENCIA_MINIMA = 3;

    protected const TIPO_AGENDAMENTO_VEICULOS = 1;

    protected const SITUACAO_EM_ANALISE = 6;

    public ?array $dataIdaVolta = [];

    public ?array $dataIda = [];

    public function mount(): void
    {
        $this->formIdaVolta->fill($this->getDefaultFormStateIdaVolta());
        $this->formIda->fill($this->getDefaultFormStateIda());
    }

    public function formIdaVolta(Schema $schema): Schema
    {
        return $schema
            ->components($this->camposIdaEVolta())
            ->statePath('dataIdaVolta');
    }

    public function formIda(Schema $schema): Schema
    {
        return $schema
            ->components($this->camposIda())
            ->statePath('dataIda');
    }
    protected function camposSolicitante(): array
    {
        return [
            Section::make('Setor solicitante')
                ->description('Selecione a unidade judiciária e o setor de onde parte a solicitação.')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Select::make('unidade_solicitante')
                        ->label('Unidade Judiciária')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->native(false)
                        ->placeholder('Selecione a unidade judiciária')
                        ->options(fn() => Setores::query()
                            ->whereColumn('id', 'codigodauo')
                            ->orderBy('unidadeorganizacional')
                            ->pluck('unidadeorganizacional', 'codigopai')
                            ->toArray())
                        ->afterStateUpdated(fn(Set $set) => $set('setor_solicitante', null))
                        ->columnSpan(1),

                    Select::make('setor_solicitante')
                        ->label('Setor solicitante')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder('Selecione o setor')
                        ->options(fn (Get $get) => blank($get('unidade_solicitante'))
                            ? []
                            : Setores::query()
                                ->where('codigodauo', $get('unidade_solicitante'))
                                ->whereColumn('id', '<>', 'codigopai')
                                ->orderBy('setor')
                                ->pluck('setor', 'id')
                                ->toArray())
                        ->disabled(fn(Get $get) => blank($get('unidade_solicitante')))
                        ->columnSpan(1),
                ])
                ->columns(2),
        ];
    }

    protected function camposIdaEVolta(): array
    {
        return [
            ...$this->camposSolicitante(),

            Section::make('Saída')
                ->description('Data, local de saída e passageiros que utilizarão o veículo.')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    DateTimePicker::make('datetime_saida')
                        ->label('Data e hora da saída')
                        ->native(false)
                        ->seconds(false)
                        ->minDate(now())
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    TextInput::make('origem')
                        ->label('Local de saída')
                        ->placeholder('Informe o local de saída')
                        ->maxLength(255)
                        ->required()
                        ->columnSpan(1),

                    TagsInput::make('passageiros')
                        ->label('Passageiros')
                        ->placeholder('Digite o nome e pressione Enter')
                        ->splitKeys([','])
                        ->required()
                        ->columnSpanFull(),

                    Radio::make('motorista')
                        ->label('Veículo com motorista')
                        ->options([
                            'Sim' => 'Sim',
                            'Nao' => 'Não',
                        ])
                        ->default('Nao')
                        ->inline()
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    FileUpload::make('doc_declaracao')
                        ->label('Documentos e declaração')
                        ->disk('egap')
                        ->directory('files/agendamento/veiculos')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Retorno')
                ->description('Data, destino e contatos para o trajeto de volta.')
                ->icon('heroicon-o-arrow-uturn-left')
                ->schema([
                    DateTimePicker::make('datetime_retorno')
                        ->label('Data e hora de volta')
                        ->native(false)
                        ->seconds(false)
                        ->after('datetime_saida')
                        ->required()
                        ->columnSpan(1),

                    TagsInput::make('destino')
                        ->label('Destino')
                        ->placeholder('Digite o destino e pressione Enter')
                        ->splitKeys([','])
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('telefone')
                        ->label('Telefone')
                        ->tel()
                        ->mask('****-****')
                        ->columnSpan(1),

                    TextInput::make('celular')
                        ->label('Celular')
                        ->tel()
                        ->mask('(**)*****-****')
                        ->columnSpan(1),
                ])
                ->columns(2),

            Section::make('Motorista aguardando no destino')
                ->description('Informe se o motorista deverá permanecer no destino durante o atendimento.')
                ->icon('heroicon-o-clock')
                ->schema([
                    Radio::make('aguardar')
                        ->label('Motorista deverá aguardar no destino')
                        ->options([
                            'Sim' => 'Sim',
                            'Nao' => 'Não',
                        ])
                        ->default('Nao')
                        ->inline()
                        ->live()
                        ->columnSpanFull(),

                    Textarea::make('justificativa_motorista')
                        ->label('Justificativa e detalhamento da permanência')
                        ->rows(3)
                        ->visible(fn(Get $get): bool => $get('aguardar') === 'Sim')
                        ->required(fn(Get $get): bool => $get('aguardar') === 'Sim')
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get): bool => $get('motorista') === 'Sim')
                ->columns(2),

            Section::make('Justificativa')
                ->schema([
                    Textarea::make('justificativa')
                        ->label('Justificativas e detalhamento do agendamento')
                        ->helperText('Informe além da justificativa todos os detalhes importantes para a execução do atendimento (ex.: quantidade de passageiros, nomes, tipo de material transportado etc).')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make('Justificativa de intempestividade')
                ->description('A solicitação está sendo feita com menos de ' . self::DIAS_UTEIS_ANTECEDENCIA_MINIMA . ' dias úteis de antecedência. Anexe a justificativa obrigatória.')
                ->icon('heroicon-o-exclamation-triangle')
                ->schema([
                    FileUpload::make('doc_just')
                        ->label('Anexo da justificativa')
                        ->disk('egap')
                        ->directory('files/agendamento/veiculos')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->columnSpanFull(),

                    Textarea::make('justificativa_escrita_intempestividade')
                        ->label('Justificativa e informações')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get): bool => $this->exigeJustificativaIntempestividade($get('datetime_saida')))
                ->columns(1),
        ];
    }

    protected function camposIda(): array
    {
        return [
            ...$this->camposSolicitante(),

            Section::make('Deslocamento')
                ->description('Data, origem, destino e passageiros do trajeto somente de ida.')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    DateTimePicker::make('datetime_saida')
                        ->label('Data e hora da saída')
                        ->native(false)
                        ->seconds(false)
                        ->minDate(now())
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    TextInput::make('origem')
                        ->label('Local de saída')
                        ->placeholder('Informe o local de saída')
                        ->maxLength(255)
                        ->required()
                        ->columnSpan(1),

                    TagsInput::make('destino')
                        ->label('Destino')
                        ->placeholder('Digite o destino e pressione Enter')
                        ->splitKeys([','])
                        ->required()
                        ->columnSpan(1),

                    TagsInput::make('passageiros')
                        ->label('Nomes dos passageiros (caso existam)')
                        ->placeholder('Digite o nome e pressione Enter')
                        ->splitKeys([','])
                        ->columnSpan(1),

                    TextInput::make('telefone')
                        ->label('Telefone')
                        ->tel()
                        ->mask('****-****')
                        ->columnSpan(1),

                    TextInput::make('celular')
                        ->label('Celular')
                        ->tel()
                        ->mask('(**)*****-****')
                        ->columnSpan(1),

                    FileUpload::make('doc_declaracao')
                        ->label('Documentos e declaração')
                        ->disk('egap')
                        ->directory('files/agendamento/veiculos')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Justificativa')
                ->schema([
                    Textarea::make('justificativa')
                        ->label('Justificativa e informações')
                        ->helperText('Informe além da justificativa todos os detalhes importantes para a execução do atendimento (ex.: quantidade de passageiros, nomes, tipo de material transportado etc).')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make('Justificativa de intempestividade')
                ->description('A solicitação está sendo feita com menos de ' . self::DIAS_UTEIS_ANTECEDENCIA_MINIMA . ' dias úteis de antecedência. Anexe a justificativa obrigatória.')
                ->icon('heroicon-o-exclamation-triangle')
                ->schema([
                    FileUpload::make('doc_just')
                        ->label('Anexo da justificativa')
                        ->disk('egap')
                        ->directory('files/agendamento/veiculos')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->columnSpanFull(),

                    Textarea::make('justificativa_escrita_intempestividade')
                        ->label('Justificativa e informações')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get): bool => $this->exigeJustificativaIntempestividade($get('datetime_saida')))
                ->columns(1),
        ];
    }

    public function enviarSolicitacaoIdaVolta(): void
    {
        $data = $this->formIdaVolta->getState();

        $solicitante = $this->resolverSolicitante($data);

        if (! $solicitante) {
            $this->notificarSolicitanteNaoIdentificado();

            return;
        }

        [$dataSaida, $horaSaida] = $this->separarDataHora($data['datetime_saida']);
        [$dataRetorno, $horaRetorno] = $this->separarDataHora($data['datetime_retorno']);

        $aguardar = $data['motorista'] === 'Sim' ? ($data['aguardar'] ?? 'Nao') : 'Nao';

        $complementoBase = [
            'telefone' => $data['telefone'] ?? null,
            'celular' => $data['celular'] ?? null,
            'doc_declaracao' => $data['doc_declaracao'] ?? null,
            'doc_just' => $data['doc_just'] ?? null,
            'justificativa' => $data['justificativa'],
            'veiculo_motorista' => $data['motorista'],
            'aguardar_destino' => $aguardar,
            'passageiros' => $data['passageiros'] ?? [],
            'justificativa_intempestividade' => $data['justificativa_escrita_intempestividade'] ?? null,
            'justificativa_motorista' => $data['justificativa_motorista'] ?? null,
        ];

        $dadosComuns = [
            'tipo' => self::TIPO_AGENDAMENTO_VEICULOS,
            'id_situacao' => self::SITUACAO_EM_ANALISE,
            'id_solicitante' => $solicitante['usuario']->id,
            'setor_solicitante' => $solicitante['setor'],
            'regiao' => $solicitante['regiao'],
            'unidade_solicitante' => $solicitante['unidade'],
        ];

        try {
            if ($data['motorista'] === 'Sim' && $aguardar === 'Nao') {
                [$ida, $volta] = DB::transaction(function () use ($data, $dadosComuns, $complementoBase, $dataSaida, $horaSaida, $dataRetorno, $horaRetorno): array {
                    $ida = Solicitacao::create([
                        ...$dadosComuns,
                        'data_inicio' => $dataSaida,
                        'hora_inicio' => $horaSaida,
                        'local_saida' => $data['origem'],
                        'justificativa' => [...$complementoBase, 'destino' => $data['destino'] ?? []],
                    ]);

                    $volta = Solicitacao::create([
                        ...$dadosComuns,
                        'data_inicio' => $dataRetorno,
                        'hora_inicio' => $horaRetorno,
                        'local_saida' => collect($data['destino'] ?? [])->last() ?? $data['origem'],
                        'justificativa' => [...$complementoBase, 'destino' => [$data['origem']]],
                    ]);

                    return [$ida, $volta];
                });

                $this->notificarSucesso("Solicitações nº {$ida->id} e nº {$volta->id} realizadas!");
            } else {
                $solicitacao = DB::transaction(fn(): Solicitacao => Solicitacao::create([
                    ...$dadosComuns,
                    'data_inicio' => $dataSaida,
                    'hora_inicio' => $horaSaida,
                    'data_termino' => $dataRetorno,
                    'hora_termino' => $horaRetorno,
                    'local_saida' => $data['origem'],
                    'justificativa' => [...$complementoBase, 'destino' => $data['destino'] ?? []],
                ]));

                $this->notificarSucesso("Solicitação nº {$solicitacao->id} realizada!");
            }

            $this->formIdaVolta->fill($this->getDefaultFormStateIdaVolta());
        } catch (Throwable $exception) {
            $this->notificarErro($exception);
        }
    }

    public function enviarSolicitacaoIda(): void
    {
        $data = $this->formIda->getState();

        $solicitante = $this->resolverSolicitante($data);

        if (! $solicitante) {
            $this->notificarSolicitanteNaoIdentificado();

            return;
        }

        [$dataInicio, $horaInicio] = $this->separarDataHora($data['datetime_saida']);

        try {
            $solicitacao = DB::transaction(fn(): Solicitacao => Solicitacao::create([
                'tipo' => self::TIPO_AGENDAMENTO_VEICULOS,
                'id_situacao' => self::SITUACAO_EM_ANALISE,
                'id_solicitante' => $solicitante['usuario']->id,
                'setor_solicitante' => $solicitante['setor'],
                'regiao' => $solicitante['regiao'],
                'unidade_solicitante' => $solicitante['unidade'],
                'data_inicio' => $dataInicio,
                'hora_inicio' => $horaInicio,
                'local_saida' => $data['origem'],
                'local_destino' => implode(', ', $data['destino'] ?? []),
                'justificativa' => [
                    'telefone' => $data['telefone'] ?? null,
                    'celular' => $data['celular'] ?? null,
                    'doc_declaracao' => $data['doc_declaracao'] ?? null,
                    'doc_just' => $data['doc_just'] ?? null,
                    'justificativa' => $data['justificativa'],
                    'veiculo_motorista' => 'Sim',
                    'aguardar_destino' => 'Nao',
                    'passageiros' => $data['passageiros'] ?? [],
                    'justificativa_intempestividade' => $data['justificativa_escrita_intempestividade'] ?? null,
                    'destino' => $data['destino'] ?? [],
                ],
            ]));

            $this->notificarSucesso("Solicitação nº {$solicitacao->id} realizada!");

            $this->formIda->fill($this->getDefaultFormStateIda());
        } catch (Throwable $exception) {
            $this->notificarErro($exception);
        }
    }

    protected function resolverSolicitante(array $data): ?array
    {
        $usuario = UserEgap::currentAuthenticated();

        if (! $usuario) {
            return null;
        }

        $unidade = $data['unidade_solicitante'];

        return [
            'usuario' => $usuario,
            'setor' => (int) $data['setor_solicitante'],
            'unidade' => (int) $unidade,
            'regiao' => Regiao::query()->where('unidade', $unidade)->value('id'),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function separarDataHora(string $dataHora): array
    {
        $carbon = Carbon::parse($dataHora);

        return [$carbon->toDateString(), $carbon->format('H:i:s')];
    }

    protected function exigeJustificativaIntempestividade(?string $dataHoraSaida): bool
    {
        if (blank($dataHoraSaida)) {
            return false;
        }

        return Carbon::parse($dataHoraSaida)->lt($this->limiteAntecedenciaMinima());
    }

    protected function limiteAntecedenciaMinima(): Carbon
    {
        $data = now();
        $diasUteis = self::DIAS_UTEIS_ANTECEDENCIA_MINIMA;

        while ($diasUteis > 0) {
            $data = $data->addDay();

            if (! $data->isWeekend()) {
                $diasUteis--;
            }
        }

        return $data;
    }

    protected function notificarSucesso(string $mensagem): void
    {
        Notification::make()
            ->title($mensagem)
            ->success()
            ->send();
    }

    protected function notificarSolicitanteNaoIdentificado(): void
    {
        Notification::make()
            ->title('Não foi possível identificar seu usuário.')
            ->body('Faça login novamente e tente enviar a solicitação outra vez.')
            ->danger()
            ->send();
    }

    protected function notificarErro(Throwable $exception): void
    {
        report($exception);

        Notification::make()
            ->title('Erro ao enviar a solicitação.')
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }

    protected function getDefaultFormStateIdaVolta(): array
    {
        return [
            'motorista' => 'Nao',
            'aguardar' => 'Nao',
        ];
    }

    protected function getDefaultFormStateIda(): array
    {
        return [];
    }

    public function render(): View
    {
        return view('egap.livewire.externo.agendamento-form');
    }
}
