<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Components;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Schemas\BemServicoForm;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoContratacaoContexto;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoWriter;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\PlanoContratacao\HistoricoPlano;
use App\Models\egap\PlanoContratacao\PlanoContratacao;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Throwable;

/**
 * Tabela de "Meus Planos" (legado: bloco `query-plano-contratacao` de
 * `pla_planodecontratacao.api.php`). Restringe aos planos do(s) setor(es) do
 * usuário e oferece as ações do fluxo: visualizar itens, editar/reenviar
 * (devolvido/rascunho), confirmar contratação e apagar.
 */
class MeusPlanosTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('id', 'Nº', isFirstColumn: true, isIdColumn: true),

                TextColumn::make('classificacao')
                    ->label('Classificação')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->alignCenter(),

                TextColumn::make('id_etapa')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => SituacaoPlano::rotulo((int) $state))
                    ->color(fn ($state): string => SituacaoPlano::tryFrom((int) $state)?->getColor() ?? 'gray')
                    ->alignCenter(),

                TableColumns::text('coordenadoriaRef.unidadeorganizacional', 'Unidade Organizacional')
                    ->description(fn (PlanoContratacao $record): ?string => $record->coordenadoriaRef?->setor),

                TableColumns::text('solicitante.name', 'Solicitante'),

                TableColumns::text('ano_execucao', 'Ano'),

                TextColumn::make('unidade_gestora')
                    ->label('Unidade Gestora')
                    ->formatStateUsing(fn (?string $state): string => mb_strtoupper((string) $state))
                    ->alignCenter(),

                TableColumns::money('preco_total', 'Valor Total'),
            ])
            ->filters([
                SelectFilter::make('id_etapa')
                    ->label('Situação')
                    ->options(SituacaoPlano::opcoesFiltro()),

                SelectFilter::make('ano_execucao')
                    ->label('Ano')
                    ->options(fn (): array => $this->getQuery()
                        ->reorder()
                        ->whereNotNull('ano_execucao')
                        ->distinct()
                        ->orderByDesc('ano_execucao')
                        ->pluck('ano_execucao')
                        ->mapWithKeys(fn ($ano): array => [(string) $ano => (string) $ano])
                        ->all()),
            ])
            ->recordActions([
                $this->acaoVisualizarItens(),
                $this->acaoEditar(),
                $this->acaoConfirmarContratacao(),
                $this->acaoEnviar(),
                $this->acaoApagar(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Nenhum plano encontrado para o seu setor');
    }

    protected function getQuery(): Builder
    {
        $setores = PlanoContratacaoContexto::setoresElegiveis();

        return PlanoContratacao::query()
            ->with(['coordenadoriaRef', 'solicitante'])
            ->when(
                $setores === [],
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->doSetor($setores),
            );
    }

    protected function acaoVisualizarItens(): Action
    {
        return TableModalAction::make('visualizarItens')
            ->label('Visualizar itens')
            ->icon('heroicon-o-eye')
            ->modalHeading(fn (PlanoContratacao $record): string => "Itens do Plano #{$record->id}")
            ->modalContent(fn (PlanoContratacao $record): View => view(
                'egap.livewire.externo.plano-contratacao.itens-visualizacao',
                ['itens' => $record->itens()->with(['naturezaDespesa', 'planoOrcamentario'])->get()],
            ));
    }

    protected function acaoEditar(): Action
    {
        return TableModalAction::make('editarSolicitacao')
            ->label('Editar solicitação')
            ->icon('heroicon-o-pencil-square')
            ->visible(fn (PlanoContratacao $record): bool => in_array((int) $record->id_etapa, SituacaoPlano::editaveis(), true))
            ->modalHeading(fn (PlanoContratacao $record): string => "Editar Plano #{$record->id}")
            ->modalSubmitAction(fn ($action) => $action->label('Salvar e reenviar'))
            ->fillForm(fn (PlanoContratacao $record): array => [
                'comentario_adm' => $record->comentario_adm,
                'itens' => $record->itens->map(fn ($item) => BemServicoForm::stateFromModel($item))->all(),
            ])
            ->schema(fn (PlanoContratacao $record): array => [
                Placeholder::make('comentario_adm')
                    ->label('Mensagem do administrador')
                    ->content(fn (): string => $record->comentario_adm ?: 'Sem mensagem.')
                    ->visible((bool) $record->comentario_adm),

                Repeater::make('itens')
                    ->label('Bens e serviços')
                    ->schema(BemServicoForm::schema($record->classificacao, incluirId: true))
                    ->itemLabel(fn (array $state): ?string => BemServicoForm::rotuloItem($state))
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->reorderable(false)
                    ->minItems(1)
                    ->addActionLabel('Adicionar bem ou serviço'),
            ])
            ->action(function (array $data, PlanoContratacao $record): void {
                $itens = $data['itens'] ?? [];

                $idsAtuais = collect($itens)->pluck('id')->filter()->map('intval')->all();
                $idsRemover = $record->itens->pluck('id')
                    ->map('intval')
                    ->reject(fn (int $id): bool => in_array($id, $idsAtuais, true))
                    ->values()
                    ->all();

                try {
                    app(PlanoWriter::class)->atualizarEReenviar(
                        plano: $record,
                        itens: $itens,
                        idsRemover: $idsRemover,
                        usuarioId: PlanoContratacaoContexto::usuarioId(),
                    );
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()
                    ->title("Plano #{$record->id} atualizado e reenviado para análise.")
                    ->success()
                    ->send();
            });
    }

    protected function acaoConfirmarContratacao(): Action
    {
        return TableModalAction::make('confirmarContratacao')
            ->label('Confirmar contratação')
            ->icon('heroicon-o-check-badge')
            ->visible(fn (PlanoContratacao $record): bool => in_array((int) $record->id_etapa, SituacaoPlano::confirmaveis(), true))
            ->modalHeading(fn (PlanoContratacao $record): string => "Confirmar contratação — Plano #{$record->id}")
            ->modalSubmitAction(fn ($action) => $action->label('Confirmar'))
            ->fillForm(fn (PlanoContratacao $record): array => [
                'itens' => $record->itens->map(fn ($item): array => [
                    'id' => $item->id,
                    'nome' => $item->nome,
                    'data_executada' => optional($item->data_executada)->toDateString(),
                    'num_processo_sei' => $item->num_processo_sei,
                ])->all(),
            ])
            ->schema([
                Repeater::make('itens')
                    ->label('Itens')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('nome')->label('Item')->disabled(),
                        DatePicker::make('data_executada')->label('Data executada'),
                        TextInput::make('num_processo_sei')->label('n° do processo SEI')->maxLength(25),
                    ])
                    ->columns(3),
            ])
            ->action(function (array $data, PlanoContratacao $record): void {
                try {
                    app(PlanoWriter::class)->confirmarContratacao(
                        plano: $record,
                        itens: $data['itens'] ?? [],
                        usuarioId: PlanoContratacaoContexto::usuarioId(),
                    );
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()
                    ->title("Contratação do Plano #{$record->id} confirmada.")
                    ->success()
                    ->send();
            });
    }

    protected function acaoEnviar(): Action
    {
        return Action::make('enviar')
            ->label('Enviar')
            ->icon('heroicon-o-paper-airplane')
            ->requiresConfirmation()
            ->modalDescription('O rascunho será enviado para análise.')
            ->visible(fn (PlanoContratacao $record): bool => in_array(
                (int) $record->id_etapa,
                [SituacaoPlano::Rascunho->value, SituacaoPlano::RascunhoLegado->value],
                true,
            ))
            ->action(function (PlanoContratacao $record): void {
                try {
                    $record->update(['id_etapa' => SituacaoPlano::EmAnalise->value]);
                    $record->itens()
                        ->where('id_etapa', '!=', SituacaoPlano::Concluido->value)
                        ->update(['id_etapa' => SituacaoPlano::EmAnalise->value]);
                    $record->refresh()->recalcularPrecoTotal();

                    HistoricoPlano::registrar($record->id, SituacaoPlano::EmAnalise->value, PlanoContratacaoContexto::usuarioId());
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()
                    ->title("Plano #{$record->id} enviado para análise.")
                    ->success()
                    ->send();
            });
    }

    protected function acaoApagar(): Action
    {
        return Action::make('apagarPlano')
            ->label('Apagar plano')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (PlanoContratacao $record): bool => in_array((int) $record->id_etapa, SituacaoPlano::editaveis(), true))
            ->action(function (PlanoContratacao $record): void {
                try {
                    app(PlanoWriter::class)->apagar($record);
                } catch (Throwable $exception) {
                    report($exception);
                    $this->notificarErro($exception);

                    return;
                }

                Notification::make()
                    ->title('Plano apagado.')
                    ->success()
                    ->send();
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
