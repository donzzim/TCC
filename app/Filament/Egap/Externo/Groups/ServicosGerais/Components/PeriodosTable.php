<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Components;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\TipoContrato;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Services\FormularioGeradorService;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Services\FormularioTacitoService;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Support\ContratoSelecionado;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\ServicosGerais\Contrato;
use App\Models\egap\ServicosGerais\Periodo;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Livewire;
use RuntimeException;

/**
 * Tabela principal do painel do gestor: períodos do contrato atualmente
 * selecionado (ver {@see ContratoSelecionado}) — substitui o corpo do
 * avagestor.php legado (tabela "Períodos" + modais "Novo Período" /
 * "Editar Período" / "Editar Nota" + botões "Criar Formulários" e "Aplicar
 * Tácito nas avaliações não respondidas").
 *
 * A distinção do legado entre editar o período inteiro (antes de gerar
 * formulários) e editar só as notas (depois) vira um único formulário aqui:
 * os campos de nome/datas ficam desabilitados quando
 * {@see Periodo::temFormularios()}, em vez de dois modais separados.
 */
class PeriodosTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const OPCOES_ATENDEU = [
        10 => 'Atendeu',
        5 => 'Parcialmente',
        0 => 'Não Atendeu',
    ];

    #[On('contrato-selecionado')]
    public function atualizarContratoSelecionado(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->headerActions([
                $this->novoPeriodoAction(),
                $this->gerenciarLocalidadesAction(),
                $this->gerenciarFiscaisAction(),
                $this->verPendentesAction(),
                $this->verParaAnaliseAction(),
                $this->aplicarTacitoAction(),
            ])
            ->columns([
                TableColumns::text('nome', 'Nome', isFirstColumn: true),
                TableColumns::date('inicio', 'Início'),
                TableColumns::date('fim', 'Fim'),
                TableColumns::text('notas', 'Notas do Período')
                    ->getStateUsing(fn(Periodo $record): string => $this->resumoNotas($record)),
                TableColumns::text('atualizadoPor.name', 'Última modificação'),
                TableColumns::text('formularios_count', 'Formulários')
                    ->counts('formularios')
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn(int $state): string => $state > 0 ? "{$state} gerados" : 'Não gerados'),
            ])
            ->recordActions([
                $this->editarAction(),
                $this->criarFormulariosAction(),
            ])
            ->paginated(false)
            ->defaultSort('inicio', 'desc')
            ->emptyStateHeading(
                blank(ContratoSelecionado::resolverAtual())
                    ? 'Selecione um contrato para visualizar os períodos'
                    : 'Nenhum período cadastrado para este contrato'
            );
    }

    private function resumoNotas(Periodo $record): string
    {
        $tipo = $record->contrato?->tipo;

        if ($tipo === null) {
            return '-';
        }

        $rotulos = $tipo->rotulosNotasPeriodo();

        return collect($rotulos)
            ->map(fn(string $rotulo, int $posicao): string => "{$rotulo}: " . ($record->{"nota_{$posicao}"} ?? '-'))
            ->implode(' | ');
    }

    private function novoPeriodoAction(): Action
    {
        return Action::make('novo_periodo')
            ->label('Novo Período')
            ->icon('heroicon-o-plus-circle')
            ->visible(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->modalHeading('Criar Novo Período')
            ->schema(fn(): array => [
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(100),
                DatePicker::make('inicio')
                    ->label('Início')
                    ->required()
                    ->native(false),
                DatePicker::make('fim')
                    ->label('Fim')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('inicio'),
                ...$this->camposNotas(),
            ])
            ->action(function (array $data): void {
                Periodo::query()->create([
                    'contrato_id' => ContratoSelecionado::resolverAtual(),
                    ...$data,
                ]);

                Notification::make()
                    ->title('Período criado com sucesso.')
                    ->success()
                    ->send();
            });
    }

    private function editarAction(): Action
    {
        return Action::make('editar_periodo')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading(fn(Periodo $record): string => "Editar {$record->nome}")
            ->fillForm(fn(Periodo $record): array => [
                'nome' => $record->nome,
                'inicio' => $record->inicio,
                'fim' => $record->fim,
                'nota_1' => $record->nota_1,
                'nota_2' => $record->nota_2,
                'nota_3' => $record->nota_3,
            ])
            ->schema(fn(Periodo $record): array => [
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(100)
                    ->disabled($record->temFormularios()),
                DatePicker::make('inicio')
                    ->label('Início')
                    ->required()
                    ->native(false)
                    ->disabled($record->temFormularios()),
                DatePicker::make('fim')
                    ->label('Fim')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('inicio')
                    ->disabled($record->temFormularios()),
                ...$this->camposNotas($record->contrato?->tipo),
            ])
            ->action(function (Periodo $record, array $data): void {
                if ($record->temFormularios()) {
                    unset($data['nome'], $data['inicio'], $data['fim']);
                }

                $record->update($data);

                Notification::make()
                    ->title('Período atualizado.')
                    ->success()
                    ->send();
            });
    }

    private function criarFormulariosAction(): Action
    {
        return Action::make('criar_formularios')
            ->label('Criar Formulários')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->visible(fn(Periodo $record): bool => $record->contrato?->vigente && ! $record->temFormularios())
            ->requiresConfirmation()
            ->modalHeading(fn(Periodo $record): string => "Criar Formulários para {$record->nome}")
            ->modalDescription('Uma vez criados, o período não poderá mais ser alterado (só a nota dada pelo gestor) nem os formulários apagados.')
            ->modalSubmitActionLabel('Criar Formulários')
            ->action(function (Periodo $record): void {
                try {
                    $quantidade = app(FormularioGeradorService::class)->gerar($record);
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->title('Não foi possível criar os formulários.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title("{$quantidade} formulário(s) criado(s) com sucesso.")
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    private function aplicarTacitoAction(): Action
    {
        return Action::make('aplicar_tacito')
            ->label('Aplicar Tácito nas Avaliações Pendentes')
            ->icon('heroicon-o-check-badge')
            ->color('warning')
            ->visible(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->requiresConfirmation()
            ->modalDescription('Formulários abertos sem fiscal atribuído serão marcados como Tácito. Formulários abertos com fiscal atribuído, mas sem resposta, seguirão para avaliação do gestor.')
            ->action(function (): void {
                $contrato = Contrato::query()->find(ContratoSelecionado::resolverAtual());

                if ($contrato === null) {
                    return;
                }

                $resultado = app(FormularioTacitoService::class)->aplicar($contrato);

                Notification::make()
                    ->title('Tácito aplicado.')
                    ->body("{$resultado['tacitos']} formulário(s) marcado(s) como Tácito, {$resultado['aguardando_avaliacao']} enviado(s) para avaliação do gestor.")
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    private function gerenciarLocalidadesAction(): Action
    {
        return TableModalAction::make('gerenciar_localidades')
            ->label('Localidades')
            ->icon('heroicon-o-map-pin')
            ->color('gray')
            ->visible(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->modalHeading('Localidades do Contrato')
            ->modalContent(fn(): HtmlString => new HtmlString(
                Livewire::mount(LocalidadesTable::class, ['contratoId' => ContratoSelecionado::resolverAtual()], 'servicos-gerais-localidades')
            ));
    }

    private function gerenciarFiscaisAction(): Action
    {
        return TableModalAction::make('gerenciar_fiscais')
            ->label('Fiscais')
            ->icon('heroicon-o-shield-check')
            ->color('gray')
            ->visible(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->modalHeading('Fiscais do Contrato')
            ->modalContent(fn(): HtmlString => new HtmlString(
                Livewire::mount(FiscaisTable::class, ['contratoId' => ContratoSelecionado::resolverAtual()], 'servicos-gerais-fiscais')
            ));
    }

    private function verPendentesAction(): Action
    {
        return TableModalAction::make('ver_pendentes')
            ->label('Avaliações Pendentes')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->visible(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->modalHeading('Avaliações Pendentes')
            ->modalContent(fn(): HtmlString => new HtmlString(
                Livewire::mount(FormulariosStatusTable::class, [
                    'contratoId' => ContratoSelecionado::resolverAtual(),
                    'status' => StatusFormulario::Aberto->value,
                ], 'servicos-gerais-pendentes')
            ));
    }

    private function verParaAnaliseAction(): Action
    {
        return TableModalAction::make('ver_para_analise')
            ->label('Para Análise das Justificativas')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('gray')
            ->visible(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->modalHeading('Para Análise das Justificativas')
            ->modalContent(fn(): HtmlString => new HtmlString(
                Livewire::mount(FormulariosStatusTable::class, [
                    'contratoId' => ContratoSelecionado::resolverAtual(),
                    'status' => StatusFormulario::AguardandoAvaliacaoGestor->value,
                ], 'servicos-gerais-para-analise')
            ));
    }

    /**
     * @return array<int, Select>
     */
    private function camposNotas(?TipoContrato $tipo = null): array
    {
        $tipo ??= Contrato::query()->find(ContratoSelecionado::resolverAtual())?->tipo;

        if ($tipo === null) {
            return [];
        }

        if ($tipo === TipoContrato::Limpeza) {
            return [
                Select::make('nota_1')
                    ->label('NTR (Tempo de Resposta)')
                    ->required()
                    ->native(false)
                    ->options([15 => 15, 12 => 12, 10 => 10, 7 => 7, 5 => 5, 0 => 0]),
                Select::make('nota_2')
                    ->label('NAP (Assiduidade Pagamento)')
                    ->required()
                    ->native(false)
                    ->options([20 => 20, 0 => 0]),
            ];
        }

        return [
            Select::make('nota_1')
                ->label('NTR (Tempo de Resposta)')
                ->required()
                ->native(false)
                ->options(self::OPCOES_ATENDEU),
            Select::make('nota_2')
                ->label('NOTF (Obrigações trabalhistas e fiscais)')
                ->required()
                ->native(false)
                ->options(self::OPCOES_ATENDEU),
            Select::make('nota_3')
                ->label('NDP (Documentação para pagamento)')
                ->required()
                ->native(false)
                ->options(self::OPCOES_ATENDEU),
        ];
    }

    private function getQuery(): Builder
    {
        $contratoId = ContratoSelecionado::resolverAtual();

        return Periodo::query()
            ->when(
                blank($contratoId),
                fn(Builder $query) => $query->whereKey(-1),
                fn(Builder $query) => $query->where('contrato_id', $contratoId)
            )
            ->withCount('formularios')
            ->with(['contrato', 'atualizadoPor']);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
