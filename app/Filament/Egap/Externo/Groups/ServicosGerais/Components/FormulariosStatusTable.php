<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Components;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusResposta;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Services\FormularioAvaliacaoService;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\ServicosGerais\Formulario;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Lista formulários de um contrato filtrados por status, aberta num modal a
 * partir de {@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Components\PeriodosTable}
 * (legado: tabelas "Avaliações Pendentes" e "Para Análise das
 * Justificativas" de avagestor.php).
 *
 * Quando o status é {@see StatusFormulario::AguardandoAvaliacaoGestor}, cada
 * linha ganha a ação "Avaliar" (legado: avalg.php/avaledit.php do lado do
 * gestor) e "Reabrir" (legado: campo `reabrir` das mesmas telas).
 */
class FormulariosStatusTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public int $contratoId;

    public string $status;

    public function mount(int $contratoId, string $status): void
    {
        $this->contratoId = $contratoId;
        $this->status = $status;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('setor.setor', 'Localidade', isFirstColumn: true)
                    ->wrap(),
                TableColumns::text('periodo.nome', 'Período'),
                TableColumns::text('fiscal.name', 'Preenchido Por')
                    ->default('Tácito'),
                TableColumns::text('tem_falta_sem_substituicao', 'Faltas sem subs.')
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Sim' : 'Não'),
            ])
            ->recordActions([
                $this->avaliarAction(),
                $this->reabrirAction(),
            ])
            ->paginated(false)
            ->defaultSort('setor.setor')
            ->emptyStateHeading('Nenhum formulário encontrado');
    }

    private function avaliarAction(): Action
    {
        return Action::make('avaliar')
            ->label('Avaliar')
            ->icon('heroicon-o-clipboard-document-check')
            ->visible(fn(Formulario $record): bool => $record->status === StatusFormulario::AguardandoAvaliacaoGestor)
            ->modalWidth('2xl')
            ->modalHeading(fn(Formulario $record): string => "Avaliar - {$record->setor?->setor}")
            ->fillForm(fn(Formulario $record): array => [
                'nota_gestor' => $record->nota_gestor,
                ...$record->respostas->mapWithKeys(fn($resposta): array => [
                    "resposta_{$resposta->id}" => $resposta->status === StatusResposta::AguardandoValidacao
                        ? StatusResposta::Validada->value
                        : $resposta->status->value,
                ])->all(),
            ])
            ->schema(fn(Formulario $record): array => [
                Select::make('nota_gestor')
                    ->label('Nota do Gestor')
                    ->required()
                    ->native(false)
                    ->options(array_combine(range(0, 10), range(0, 10))),
                ...$record->respostas->map(fn($resposta) => Select::make("resposta_{$resposta->id}")
                    ->label("{$resposta->pergunta?->texto} ({$resposta->estrelas} estrelas)")
                    ->required()
                    ->native(false)
                    ->options([
                        StatusResposta::Validada->value => 'Validado',
                        StatusResposta::Invalidada->value => 'Invalidado',
                    ]))->all(),
            ])
            ->action(function (Formulario $record, array $data): void {
                $service = app(FormularioAvaliacaoService::class);
                $service->definirNotaGestor($record, (int) $data['nota_gestor']);

                foreach ($record->respostas as $resposta) {
                    $novoStatus = $data["resposta_{$resposta->id}"] ?? null;

                    if ($novoStatus !== null) {
                        $service->validarResposta($resposta, StatusResposta::from($novoStatus));
                    }
                }

                Notification::make()
                    ->title('Avaliação registrada.')
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    private function reabrirAction(): Action
    {
        return Action::make('reabrir')
            ->label('Reabrir')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->visible(fn(Formulario $record): bool => $record->status !== StatusFormulario::Aberto)
            ->requiresConfirmation()
            ->modalDescription('O formulário voltará para "Aberto" e o fiscal precisará responder novamente.')
            ->action(function (Formulario $record): void {
                app(FormularioAvaliacaoService::class)->reabrir($record);

                Notification::make()
                    ->title('Formulário reaberto.')
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    private function getQuery(): Builder
    {
        return Formulario::query()
            ->whereHas('periodo', fn(Builder $query) => $query->where('contrato_id', $this->contratoId))
            ->where('status', $this->status)
            ->with(['setor', 'periodo', 'fiscal', 'respostas.pergunta']);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
