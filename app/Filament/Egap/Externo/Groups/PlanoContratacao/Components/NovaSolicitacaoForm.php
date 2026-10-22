<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Components;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Schemas\BemServicoForm;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoContratacaoContexto;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Support\PlanoWriter;
use App\Models\egap\PlanoContratacao\LimitePlano;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Formulário da Nova Solicitação de Plano de Contratação (legado:
 * `pla_novasolicitacao.php` / `pla_novasolicitacao.api.php`).
 *
 * Cabeçalho (solicitante/setor do contexto + classificação + unidade gestora) e
 * repeater de itens de bem/serviço ({@see BemServicoForm}). Grava via
 * {@see PlanoWriter}: "Salvar rascunho" -> etapa 13; "Enviar" -> etapa 6 e
 * consumo do limite do setor.
 */
class NovaSolicitacaoForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'classificacao' => null,
            'unidade_gestora' => null,
            'itens' => [],
        ]);
    }

    #[On('usuario-selecionado')]
    #[On('setor-selecionado')]
    public function recarregarContexto(): void
    {
        // Redesenha os placeholders de solicitante/setor e o valor disponível.
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da solicitação')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('solicitante')
                            ->label('Solicitante')
                            ->content(fn (): string => EgapSession::info()['nome'] ?? '—'),

                        Placeholder::make('setor')
                            ->label('Setor')
                            ->content(fn (): string => EgapSession::rotulosLotacaoAtual()['setor'] ?? '—'),

                        Select::make('classificacao')
                            ->label('Tipo de plano de contratação')
                            ->options([
                                'investimento' => 'Investimento',
                                'custeio' => 'Custeio',
                            ])
                            ->required()
                            ->live()
                            ->native(false),

                        Select::make('unidade_gestora')
                            ->label('Unidade Gestora')
                            ->options([
                                'tj' => 'TJ',
                                'funepj' => 'FUNEPJ',
                            ])
                            ->required()
                            ->native(false),

                        Placeholder::make('valor_disponivel')
                            ->label('Valor disponível (limite do setor)')
                            ->content(fn (): string => $this->valorDisponivelFormatado())
                            ->columnSpanFull(),
                    ]),

                Repeater::make('itens')
                    ->label('Bens e serviços')
                    ->addActionLabel('Adicionar bem ou serviço')
                    ->schema(BemServicoForm::schema())
                    ->itemLabel(fn (array $state): ?string => BemServicoForm::rotuloItem($state))
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->reorderable(false)
                    ->minItems(1)
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function salvarRascunho(): void
    {
        $this->persistir(SituacaoPlano::Rascunho, 'Rascunho salvo.');
    }

    public function enviar(): void
    {
        $this->persistir(SituacaoPlano::EmAnalise, 'Solicitação enviada para análise.');
    }

    protected function persistir(SituacaoPlano $etapa, string $mensagemSucesso): void
    {
        $usuarioId = PlanoContratacaoContexto::usuarioId();
        $setorId = PlanoContratacaoContexto::setorId();

        if (blank($usuarioId) || blank($setorId)) {
            Notification::make()
                ->title('Identifique o usuário e o setor antes de continuar.')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();

        if (($state['itens'] ?? []) === []) {
            Notification::make()
                ->title('Adicione ao menos um item de bem/serviço.')
                ->danger()
                ->send();

            return;
        }

        try {
            $plano = app(PlanoWriter::class)->criar(
                cabecalho: [
                    'classificacao' => $state['classificacao'],
                    'unidade_gestora' => $state['unidade_gestora'],
                    'cd_coordenadoria' => (int) $setorId,
                    'ano_execucao' => PlanoContratacaoContexto::anoExecucaoPadrao(),
                ],
                itens: $state['itens'],
                etapa: $etapa,
                usuarioId: (int) $usuarioId,
            );
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível salvar a solicitação.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($mensagemSucesso)
            ->body("Plano de Contratação #{$plano->id} — {$plano->itens()->count()} item(ns).")
            ->success()
            ->send();

        $this->form->fill([
            'classificacao' => null,
            'unidade_gestora' => null,
            'itens' => [],
        ]);

        $this->dispatch('plano-salvo');
    }

    public function getValorTotalProperty(): float
    {
        return collect($this->data['itens'] ?? [])
            ->sum(fn (array $item): float => (float) ($item['valor1'] ?? 0) + (float) ($item['valor2'] ?? 0));
    }

    protected function valorDisponivelFormatado(): string
    {
        $setorId = PlanoContratacaoContexto::setorId();

        if (blank($setorId)) {
            return '—';
        }

        $disponivel = LimitePlano::disponivelPara((int) $setorId, PlanoContratacaoContexto::anoExecucaoPadrao());

        if ($disponivel === null) {
            return 'Sem limite cadastrado para o setor neste ano.';
        }

        return 'R$ '.number_format($disponivel, 2, ',', '.');
    }

    public function render(): View
    {
        return view('egap.livewire.externo.plano-contratacao.nova-solicitacao-form');
    }
}
