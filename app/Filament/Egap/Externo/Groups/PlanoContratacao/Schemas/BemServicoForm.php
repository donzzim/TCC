<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Schemas;

use App\Filament\Egap\Support\MoneyInput;
use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Cadastro\UnidadesDeMedida;
use App\Models\egap\GestaoOrcamentaria\PlanoOrcamentario;
use App\Models\egap\PlanoContratacao\BemServico;
use App\Models\egap\PlanoContratacao\IniciativaEstrategica;
use App\Models\egap\PlanoContratacao\ObjetivoEstrategico;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Campos de um item de bem/serviço (`pla_bensservicos`), compartilhados entre o
 * repeater da Nova Solicitação e o modal de edição de "Meus Planos".
 *
 * Espelha o modal "Adicionar bens e serviços" do legado
 * (`pla_novasolicitacao.php` / `pla_planodecontratacao.php`). Valores gravados
 * mantêm exatamente as strings do legado:
 * - `classificacao`: `projeto` | `atividade`
 * - `prioridade`: `baixa` | `media` | `alta`
 * - `renovacao_contrato`: `sim` | `nao`
 * - `sus_acess`: `nao` | `ambos` | `sustentabilidade` | `acessibilidade`
 * - `id_naturezadespesa` guarda `mat_produtos.CodigodaClasse`
 * - `id_planoorcamentario` guarda `orc_planoorcamentario.codigo`
 */
class BemServicoForm
{
    /**
     * @param  string|null  $classificacaoPlano  investimento|custeio — filtra a
     *                                           natureza de despesa (4% / 3%).
     *                                           Usado como fallback quando o
     *                                           schema não está dentro do
     *                                           repeater da Nova Solicitação.
     * @param  bool  $incluirExecucao  exibe os campos de confirmação da
     *                                 contratação (data executada / SEI).
     * @param  bool  $incluirId  guarda o id do item existente (edição em modal,
     *                           fora do repeater da Nova Solicitação).
     * @return array<int, Component>
     */
    public static function schema(?string $classificacaoPlano = null, bool $incluirExecucao = false, bool $incluirId = false): array
    {
        return array_values(array_filter([
            $incluirId ? Hidden::make('id') : null,

            Section::make('Identificação')
                ->columns(2)
                ->schema([
                    TextInput::make('num_processo_sei')
                        ->label('N° do processo SEI')
                        ->mask('9999999-99.9999.9.99.9999')
                        ->placeholder('0000000-00.0000.0.00.0000')
                        ->maxLength(25),

                    TextInput::make('cod_item')
                        ->label('Código do item')
                        ->numeric(),

                    Select::make('id_naturezadespesa')
                        ->label('Natureza da Despesa')
                        ->options(fn (Get $get): array => self::naturezaOpcoes($get('../../classificacao') ?? $classificacaoPlano))
                        ->searchable()
                        ->required()
                        ->native(false),

                    Select::make('tipo_item')
                        ->label('Tipo do Item')
                        ->options(fn (): array => self::tipoItemOpcoes())
                        ->searchable()
                        ->native(false),

                    TextInput::make('nome')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),
                ]),

            Section::make('Planejamento')
                ->columns(2)
                ->schema([
                    Select::make('id_planoorcamentario')
                        ->label('Plano Orçamentário')
                        ->options(fn (): array => self::planoOrcamentarioOpcoes())
                        ->searchable()
                        ->required()
                        ->native(false),

                    Select::make('classificacao')
                        ->label('Classificação da ação')
                        ->options([
                            'projeto' => 'Projeto',
                            'atividade' => 'Atividade',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('prioridade')
                        ->label('Prioridade')
                        ->options([
                            'baixa' => 'Baixa',
                            'media' => 'Média',
                            'alta' => 'Alta',
                        ])
                        ->default('baixa')
                        ->required()
                        ->native(false),

                    Select::make('sus_acess')
                        ->label('Critérios de sustentabilidade / acessibilidade')
                        ->options([
                            'nao' => 'Não',
                            'ambos' => 'Ambos',
                            'sustentabilidade' => 'Sustentabilidade',
                            'acessibilidade' => 'Acessibilidade',
                        ])
                        ->default('nao')
                        ->required()
                        ->native(false),

                    Select::make('renovacao_contrato')
                        ->label('Renovação de contrato?')
                        ->options([
                            'sim' => 'Sim',
                            'nao' => 'Não',
                        ])
                        ->default('nao')
                        ->required()
                        ->native(false),

                    Select::make('obj_estrategico')
                        ->label('Objetivo Estratégico')
                        ->options(fn (): array => self::objetivoOpcoes())
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('ini_estrategica', null)),

                    Select::make('ini_estrategica')
                        ->label('Iniciativa Estratégica')
                        ->options(fn (Get $get): array => self::iniciativaOpcoes($get('obj_estrategico')))
                        ->searchable()
                        ->native(false)
                        ->disabled(fn (Get $get): bool => blank($get('obj_estrategico'))),
                ]),

            Section::make('Datas')
                ->columns($incluirExecucao ? 3 : 2)
                ->schema(array_filter([
                    DatePicker::make('data_estimada')
                        ->label('Data estimada para contratação')
                        ->required(),

                    DatePicker::make('data_inicio')
                        ->label('Início dos procedimentos'),

                    $incluirExecucao
                        ? DatePicker::make('data_executada')->label('Data de execução')
                        : null,
                ])),

            Section::make('Valores')
                ->columns(3)
                ->schema([
                    TextInput::make('quantidade')
                        ->label('Quantidade')
                        ->numeric()
                        ->minValue(0),

                    MoneyInput::make('valor1')
                        ->label('Valor 1º Grau')
                        ->required(),

                    MoneyInput::make('valor2')
                        ->label('Valor 2º Grau')
                        ->required(),

                    MoneyInput::make('valor_global')
                        ->label('Valor Global da Contratação'),

                    MoneyInput::make('valor_desembolsado_ano_pca')
                        ->label('Valor Desembolsado Ano PCA'),
                ]),

            Textarea::make('justificativa')
                ->label('Justificativa da necessidade da contratação')
                ->required()
                ->maxLength(500)
                ->rows(3)
                ->columnSpanFull(),
        ]));
    }

    /**
     * Rótulo de um item no repeater / itemLabel.
     */
    public static function rotuloItem(array $state): ?string
    {
        return filled($state['nome'] ?? null) ? $state['nome'] : 'Novo item';
    }

    /**
     * Converte um item persistido em estado de formulário (edição em modal).
     *
     * @return array<string, mixed>
     */
    public static function stateFromModel(BemServico $item): array
    {
        return [
            'id' => $item->id,
            'num_processo_sei' => $item->num_processo_sei,
            'cod_item' => $item->cod_item,
            'id_naturezadespesa' => $item->id_naturezadespesa,
            'tipo_item' => $item->tipo_item,
            'nome' => $item->nome,
            'id_planoorcamentario' => $item->id_planoorcamentario,
            'classificacao' => $item->classificacao,
            'prioridade' => $item->prioridade,
            'sus_acess' => $item->sus_acess,
            'renovacao_contrato' => $item->renovacao_contrato,
            'obj_estrategico' => $item->obj_estrategico,
            'ini_estrategica' => $item->ini_estrategica,
            'data_estimada' => optional($item->data_estimada)->toDateString(),
            'data_inicio' => optional($item->data_inicio)->toDateString(),
            'data_executada' => optional($item->data_executada)->toDateString(),
            'quantidade' => $item->quantidade,
            'valor1' => $item->valor1,
            'valor2' => $item->valor2,
            'valor_global' => $item->valor_global,
            'valor_desembolsado_ano_pca' => $item->valor_desembolsado_ano_pca,
            'justificativa' => $item->justificativa,
        ];
    }

    /** @return array<string, string> */
    public static function naturezaOpcoes(?string $classificacaoPlano): array
    {
        $prefixo = match ($classificacaoPlano) {
            'investimento' => '4%',
            'custeio' => '3%',
            default => null,
        };

        return ElementoDespesa::query()
            ->when($prefixo, fn ($query) => $query->where('CodigodaClasse', 'like', $prefixo))
            ->orderBy('CodigodaClasse')
            ->get(['CodigodaClasse', 'DescricaodaClasse'])
            ->mapWithKeys(fn ($row): array => [
                $row->CodigodaClasse => trim($row->CodigodaClasse.' / '.$row->DescricaodaClasse),
            ])
            ->all();
    }

    /** @return array<string, string> */
    public static function planoOrcamentarioOpcoes(): array
    {
        return PlanoOrcamentario::query()
            ->orderBy('nome')
            ->get(['codigo', 'nome'])
            ->mapWithKeys(fn ($row): array => [
                $row->codigo => trim($row->codigo.' - '.$row->nome),
            ])
            ->all();
    }

    /** @return array<string, string> */
    public static function tipoItemOpcoes(): array
    {
        return UnidadesDeMedida::query()
            ->orderBy('Sigla')
            ->pluck('Sigla', 'Sigla')
            ->map(fn ($sigla): string => mb_strtoupper((string) $sigla))
            ->all();
    }

    /** @return array<int, string> */
    public static function objetivoOpcoes(): array
    {
        return ObjetivoEstrategico::query()
            ->orderBy('objetivo')
            ->pluck('objetivo', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public static function iniciativaOpcoes(mixed $objetivoId): array
    {
        if (blank($objetivoId)) {
            return [];
        }

        return IniciativaEstrategica::query()
            ->doObjetivo((int) $objetivoId)
            ->orderBy('iniciativa')
            ->pluck('iniciativa', 'id')
            ->all();
    }
}
