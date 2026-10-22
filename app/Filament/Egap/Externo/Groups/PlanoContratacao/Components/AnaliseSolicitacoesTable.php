<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Components;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoContratacaoContexto;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoWriter;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\PlanoContratacao\PlanoContratacao;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Tabela administrativa de análise de solicitações (legado:
 * `pla_telaadministrador.api.php`, blocos `solicitacoes_pla_filtros` e
 * `modificacao-solicitacao`).
 */
class AnaliseSolicitacoesTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(
                PlanoContratacao::query()
                    ->with(['coordenadoriaRef', 'solicitante'])
                    ->where('id_etapa', '!=', SituacaoPlano::Rascunho->value)
            )
            ->columns([
                TableColumns::text('id', 'Nº', isFirstColumn: true, isIdColumn: true),

                TextColumn::make('classificacao')
                    ->label('Classificação')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->alignCenter(),

                TableColumns::text('coordenadoriaRef.unidadeorganizacional', 'Unidade Organizacional')->wrap(),

                TableColumns::text('coordenadoriaRef.setor', 'Setor')->wrap(),

                TextColumn::make('id_etapa')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => SituacaoPlano::rotulo((int) $state))
                    ->color(fn ($state): string => SituacaoPlano::tryFrom((int) $state)?->getColor() ?? 'gray')
                    ->alignCenter(),

                TextColumn::make('unidade_gestora')
                    ->label('Unidade Gestora')
                    ->formatStateUsing(fn (?string $state): string => mb_strtoupper((string) $state))
                    ->alignCenter(),

                TableColumns::text('solicitante.name', 'Solicitante')->wrap(),

                TableColumns::text('ano_execucao', 'Ano'),

                TableColumns::money('preco_total', 'Valor Total'),
            ])
            ->filters([
                SelectFilter::make('classificacao')
                    ->label('Tipo')
                    ->options(['investimento' => 'Investimento', 'custeio' => 'Custeio']),

                SelectFilter::make('unidade_gestora')
                    ->label('Unidade Gestora')
                    ->options(['tj' => 'TJ', 'funepj' => 'FUNEPJ']),

                SelectFilter::make('id_etapa')
                    ->label('Situação')
                    ->options(SituacaoPlano::opcoesFiltro()),

                SelectFilter::make('ano_execucao')
                    ->label('Ano')
                    ->options(fn (): array => PlanoContratacao::query()
                        ->whereNotNull('ano_execucao')
                        ->distinct()
                        ->orderByDesc('ano_execucao')
                        ->pluck('ano_execucao')
                        ->mapWithKeys(fn ($ano): array => [(string) $ano => (string) $ano])
                        ->all()),
            ])
            ->recordActions([
                $this->acaoDetalhar(),
                $this->acaoHistorico(),
                $this->acaoAprovar(),
                $this->acaoDevolver(),
                $this->acaoApagar(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Nenhuma solicitação encontrada');
    }

    protected function acaoDetalhar(): Action
    {
        return TableModalAction::make('detalhar')
            ->label('Detalhar')
            ->icon('heroicon-o-eye')
            ->modalHeading(fn (PlanoContratacao $record): string => "Itens do Plano #{$record->id}")
            ->modalContent(fn (PlanoContratacao $record): View => view(
                'egap.livewire.externo.plano-contratacao.itens-visualizacao',
                ['itens' => $record->itens()->with(['naturezaDespesa', 'planoOrcamentario'])->get()],
            ));
    }

    protected function acaoHistorico(): Action
    {
        return TableModalAction::make('historico')
            ->label('Histórico')
            ->icon('heroicon-o-clock')
            ->modalHeading(fn (PlanoContratacao $record): string => "Histórico do Plano #{$record->id}")
            ->modalContent(fn (PlanoContratacao $record): View => view(
                'egap.livewire.externo.plano-contratacao.historico',
                ['historico' => $record->historico()->with(['usuario'])->orderByDesc('data_modificacao')->get()],
            ));
    }

    protected function acaoAprovar(): Action
    {
        return Action::make('aprovar')
            ->label('Aprovar')
            ->icon('heroicon-o-check')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (PlanoContratacao $record): bool => (int) $record->id_etapa === SituacaoPlano::EmAnalise->value)
            ->action(function (PlanoContratacao $record): void {
                try {
                    app(PlanoWriter::class)->aprovar($record, PlanoContratacaoContexto::usuarioId());
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()->title("Plano #{$record->id} aprovado.")->success()->send();
            });
    }

    protected function acaoDevolver(): Action
    {
        return Action::make('devolver')
            ->label('Devolver')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (PlanoContratacao $record): bool => (int) $record->id_etapa === SituacaoPlano::EmAnalise->value)
            ->schema([
                Textarea::make('justificativa')
                    ->label('Mensagem para o solicitante')
                    ->required()
                    ->rows(4)
                    ->maxLength(500),
            ])
            ->action(function (array $data, PlanoContratacao $record): void {
                try {
                    app(PlanoWriter::class)->devolver($record, $data['justificativa'], PlanoContratacaoContexto::usuarioId());
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()->title("Plano #{$record->id} devolvido ao solicitante.")->success()->send();
            });
    }

    protected function acaoApagar(): Action
    {
        return Action::make('apagar')
            ->label('Apagar')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('O plano e seus itens serão removidos permanentemente.')
            ->action(function (PlanoContratacao $record): void {
                try {
                    app(PlanoWriter::class)->apagar($record);
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()->title('Plano apagado.')->success()->send();
            });
    }

    protected function notificarErro(Throwable $exception): void
    {
        Notification::make()
            ->title('Não foi possível concluir a operação.')
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
