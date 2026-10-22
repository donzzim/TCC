<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Components;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Services\FormularioAvaliacaoService;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\ServicosGerais\ContratoFiscal;
use App\Models\egap\ServicosGerais\Formulario;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Painel do fiscal: formulários das localidades sob sua responsabilidade,
 * pendentes e já respondidos (legado: avafiscal.php).
 *
 * Simplificação deliberada: o legado separava os formulários em abas por
 * tipo de contrato (Limpeza/Telefonia/Copeiragem/Transporte/Água). Aqui é
 * uma tabela única com filtro de status — o tipo de contrato aparece como
 * coluna. Só cobre a modalidade de questionário por estrelas (li/co/te);
 * Transporte e Ateste de Água ainda não têm o modelo de atestado por
 * trabalhador migrado (ver {@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\TipoContrato::usaQuestionario()}).
 */
class FormulariosFiscalTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    #[On('usuario-selecionado')]
    public function atualizarUsuarioSelecionado(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('periodo.contrato.nome', 'Contrato', isFirstColumn: true)
                    ->wrap(),
                TableColumns::text('setor.setor', 'Localidade')
                    ->wrap(),
                TableColumns::text('periodo.nome', 'Período'),
                TableColumns::text('status', 'Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(StatusFormulario::cases())->mapWithKeys(fn(StatusFormulario $status): array => [$status->value => $status->getLabel()])),
            ])
            ->recordActions([
                $this->responderAction(),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Nenhum formulário encontrado para os seus setores');
    }

    private function responderAction(): Action
    {
        return Action::make('responder')
            ->label('Responder')
            ->icon('heroicon-o-pencil-square')
            ->visible(fn(Formulario $record): bool => $record->status === StatusFormulario::Aberto)
            ->modalWidth('2xl')
            ->modalHeading(fn(Formulario $record): string => "Responder - {$record->setor?->setor}")
            ->schema(fn(Formulario $record): array => [
                Toggle::make('tem_falta_sem_substituicao')
                    ->label('Ocorreu falta sem substituição?')
                    ->live(),
                Textarea::make('observacao_falta')
                    ->label('Observação sobre a falta')
                    ->visible(fn(Get $get): bool => (bool) $get('tem_falta_sem_substituicao'))
                    ->rows(3),
                ...$record->respostas->map(fn($resposta) => Select::make("estrelas_{$resposta->id}")
                    ->label($resposta->pergunta?->texto)
                    ->required()
                    ->native(false)
                    ->options([0 => '0 estrelas', 1 => '1 estrela', 2 => '2 estrelas', 3 => '3 estrelas', 4 => '4 estrelas', 5 => '5 estrelas']))->all(),
            ])
            ->action(function (Formulario $record, array $data): void {
                $respostas = [];

                foreach ($record->respostas as $resposta) {
                    $respostas[$resposta->pergunta_id] = [
                        'estrelas' => (int) ($data["estrelas_{$resposta->id}"] ?? 0),
                    ];
                }

                app(FormularioAvaliacaoService::class)->responderComoFiscal(
                    $record,
                    $respostas,
                    (bool) ($data['tem_falta_sem_substituicao'] ?? false),
                    $data['observacao_falta'] ?? null,
                );

                Notification::make()
                    ->title('Avaliação enviada.')
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    /**
     * Formulários cuja localidade está atribuída ao fiscal autenticado no
     * mesmo contrato (par contrato+setor, ver {@see ContratoFiscal}).
     */
    private function getQuery(): Builder
    {
        $usuarioId = UsuarioSelecionado::resolverAtual();

        if (blank($usuarioId)) {
            return Formulario::query()->whereKey(-1);
        }

        $atribuicoes = ContratoFiscal::query()
            ->where('user_id', $usuarioId)
            ->get(['contrato_id', 'setor_id']);

        if ($atribuicoes->isEmpty()) {
            return Formulario::query()->whereKey(-1);
        }

        $contratoIds = $atribuicoes->pluck('contrato_id')->unique();

        return Formulario::query()
            ->whereHas('periodo', fn(Builder $query) => $query->whereIn('contrato_id', $contratoIds))
            ->with(['setor', 'periodo.contrato', 'respostas.pergunta'])
            ->get()
            ->filter(fn(Formulario $formulario): bool => $atribuicoes->contains(
                fn($atribuicao): bool => $atribuicao->contrato_id === $formulario->periodo->contrato_id
                    && $atribuicao->setor_id === $formulario->setor_id
            ))
            ->pluck('id')
            ->pipe(fn($ids) => Formulario::query()
                ->whereIn('id', $ids)
                ->with(['setor', 'periodo.contrato', 'respostas.pergunta']));
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
