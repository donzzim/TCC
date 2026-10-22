<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Concerns;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Support\ContratoSelecionado;
use App\Models\egap\ServicosGerais\Contrato;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * Compartilha, entre as páginas de gestor de Serviços Gerais, a seleção do
 * contrato atual (ver {@see ContratoSelecionado}) — substitui o seletor de
 * contrato + $_SESSION["csg_contrato_selecionado"] do avagestor.php legado.
 *
 * Se o usuário gere um único contrato, ele é selecionado automaticamente
 * (o legado sempre exigia a escolha manual, mesmo havendo só uma opção).
 *
 * Este trait não expõe `mount()` diretamente: quem monta a página chama
 * {@see resolverContratoAtual()} explicitamente. Ele sobreviveu à remoção dos
 * modais de setor e usuário — que viraram {@see \App\Filament\Egap\Auth\EgapSession} —
 * porque aqui a escolha é real: um gestor pode gerir mais de um contrato, e
 * não há nada na sessão do usuário que decida qual.
 */
trait SelecionaContratoAtual
{
    public function resolverContratoAtual(): void
    {
        $atual = ContratoSelecionado::resolverAtual();

        if (filled($atual) && ContratoSelecionado::podeGerir($atual)) {
            return;
        }

        ContratoSelecionado::limpar();

        $meusContratos = ContratoSelecionado::meusContratosIds();

        if ($meusContratos->isEmpty()) {
            return;
        }

        if ($meusContratos->count() === 1) {
            $contratoId = (int) $meusContratos->first();

            ContratoSelecionado::selecionar($contratoId);

            $this->dispatch('contrato-selecionado', contrato: $contratoId);

            return;
        }

        $this->mountAction('selecionarContrato');
    }

    public function selecionarContratoHeaderAction(): Action
    {
        return $this->selecionarContratoAction()
            ->label('Trocar Contrato')
            ->icon('heroicon-o-arrow-path')
            ->color('gray');
    }

    public function selecionarContratoAction(): Action
    {
        return Action::make('selecionarContrato')
            ->modalHeading('Selecione o contrato')
            ->modalDescription('Escolha qual contrato sob sua gestão você quer visualizar.')
            ->modalSubmitActionLabel('Selecionar')
            //->modalCancelAction(fn(): bool => filled(ContratoSelecionado::resolverAtual()))
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->fillForm(fn(): array => [
                'contrato_id' => ContratoSelecionado::resolverAtual(),
            ])
            ->schema([
                Select::make('contrato_id')
                    ->label('Contrato')
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->options(fn(): array => Contrato::query()
                        ->whereIn('id', ContratoSelecionado::meusContratosIds())
                        ->orderBy('nome')
                        ->pluck('nome', 'id')
                        ->toArray()),
            ])
            ->action(function (array $data): void {
                ContratoSelecionado::selecionar((int) $data['contrato_id']);

                $this->dispatch('contrato-selecionado', contrato: (int) $data['contrato_id']);

                $nome = Contrato::query()->find($data['contrato_id'])?->nome;

                Notification::make()
                    ->title('Visualizando contrato:')
                    ->body($nome)
                    ->info()
                    ->send();
            });
    }
}
