<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\ItemInventario;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Relação de bens já conferidos no inventário online do setor do usuário
 * autenticado do Ambiente Externo, para a Comissão assinar eletronicamente o
 * Termo de Responsabilidade (legado: levantamento_comissao.php +
 * api/assinatura.api.php + termo-inventario-eletronico.php).
 *
 * Não replicado do legado: a reconfirmação de senha antes de assinar — a
 * sessão autenticada do Filament já garante a identidade do usuário, mesmo
 * padrão adotado em {@see MovimentacaoDeMateriaisTable::assinarEletronicamenteAction()}.
 * A assinatura eletrônica também é imediata (situação Validado), pois o
 * documento gerado já nasce assinado (sem etapa de validação posterior pela
 * Seção de Patrimônio, diferente do fluxo de transferência de bens).
 */
class LevantamentoComissaoTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const SITUACAO_A_INVENTARIAR = 'A INVENTARIAR';

    public ?int $setorAtual = null;

    public function mount(): void
    {
        $this->setorAtual = SetorSelecionado::resolverAtual();
    }

    #[On('setor-selecionado')]
    public function atualizarSetorSelecionado(int $setor): void
    {
        $this->setorAtual = $setor;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('num_patrimonio', 'Patrimônio', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TableColumns::text('num_patrimonioantigo', 'Patrimônio Antigo')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('num_serie', 'Núm. Série')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('descricao_detalhada', 'Descrição')
                    ->wrap()
                    ->description(fn(ItemInventario $record): ?string => $record->marca ?? null),

                TableColumns::text('estado_conservacao', 'Estado')
                    ->badge()
                    ->color(fn(?string $state): string => match (strtoupper(trim((string) $state))) {
                        'ÓTIMO', 'BOM' => 'success',
                        'REGULAR' => 'warning',
                        'RUIM', 'SUCATA' => 'danger',
                        default => 'gray',
                    }),

                TableColumns::text('id_complementosetor', 'Complemento do Setor')
                    ->wrap()
                    ->formatStateUsing(fn(?string $state, ItemInventario $record): string => strtoupper(trim((string) ($record->complementoSetorRef?->descricao ?? $record->complemento_localizado))) ?: '-'),

                TableColumns::text('situacao', 'Situação')
                    ->formatStateUsing(fn(?string $state): string => match (strtoupper(trim((string) $state))) {
                        'LOCALIZADO/AJUSTES' => 'AJUSTES',
                        '' => '-',
                        default => strtoupper(trim((string) $state)),
                    })
                    ->badge()
                    ->color(fn(?string $state): string => match (strtoupper(trim((string) $state))) {
                        'LOCALIZADO/AJUSTES' => 'warning',
                        'LOCALIZADO' => 'success',
                        'LOCALIZADO/NOVO' => 'info',
                        'EM TRANSFERENCIA', 'EM TRANSFERÊNCIA' => 'warning',
                        'NÃO LOCALIZADO', 'NAO LOCALIZADO' => 'danger',
                        default => 'gray',
                    }),

                TableColumns::text('termo', 'Termo')
                    ->tooltip('Clique para visualizar o termo')
                    ->weight('bold')
                    ->formatStateUsing(fn($state, ItemInventario $record): string => $record->termoRef?->termo_completo ?? '-')
                    ->description(fn(ItemInventario $record): ?string => $this->assinaturaTermo($record))
                    ->color('primary')
                    ->url(fn(ItemInventario $record): ?string => filled($record->termoRef?->arquivoDigital?->arquivo_digital)
                        ? config('app.egap') . $record->termoRef->arquivoDigital->arquivo_digital
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('id_complementosetor')
                    ->label('Complemento do Setor')
                    ->multiple()
                    ->options(fn(): array => $this->complementosOptions()),
            ])
            ->headerActions([
                $this->assinarTermosAction(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('num_patrimonio')
            ->emptyStateHeading(
                blank($this->setorAtual)
                    ? 'Não foi possível identificar o setor do usuário atual.'
                    : 'Nenhum bem inventariado encontrado'
            );
    }

    private function assinarTermosAction(): Action
    {
        return Action::make('assinar_termos')
            ->label('Assinar Termos')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            ->modalHeading('Assinar Termos de Responsabilidade')
            ->modalDescription('Será gerado um Termo de Responsabilidade para cada complemento selecionado, reunindo os bens já conferidos e ainda sem termo. A assinatura eletrônica é efetivada imediatamente, em nome do usuário autenticado.')
            ->modalSubmitActionLabel('Assinar')
            ->disabled(fn(): bool => blank($this->setorAtual))
            ->schema([
                Select::make('complementos')
                    ->label('Complemento(s) do Setor')
                    ->required()
                    ->multiple()
                    ->preload()
                    ->native(false)
                    ->options(fn(): array => $this->complementosPendentesOptions())
                    ->helperText('Somente complementos com bens conferidos e ainda não assinados aparecem aqui.'),
            ])
            ->action(fn(array $data) => $this->assinarTermos($data['complementos']));
    }

    private function assinarTermos(array $complementoIds): void
    {
        $termosGerados = collect();

        foreach ($complementoIds as $complementoId) {
            $itens = ItemInventario::query()
                ->where('setor', $this->setorAtual)
                ->where('id_complementosetor', $complementoId)
                ->where('situacao', '<>', self::SITUACAO_A_INVENTARIAR)
                ->whereNull('termo')
                ->get();

            if ($itens->isEmpty()) {
                continue;
            }

            $termo = DB::transaction(function () use ($itens): Termo {
                $ano = (int) now()->year;

                $ultimoTermo = Termo::query()
                    ->where('ano_termo', $ano)
                    ->orderByDesc('num_termo')
                    ->lockForUpdate()
                    ->first(['num_termo']);

                $termo = Termo::query()->create([
                    'num_termo' => ((int) $ultimoTermo?->num_termo) + 1,
                    'ano_termo' => $ano,
                    'situacao_entrega' => 'Concluído',
                ]);

                $termo->arquivoDigital()->create([
                    'situacao' => ArquivoDigital::SITUACAO_VALIDADO,
                    'web' => 1,
                    'data_validacao' => now(),
                    'validado_por' => auth()->id(),
                ]);

                ItemInventario::query()
                    ->whereKey($itens->pluck('id'))
                    ->update(['termo' => $termo->id]);

                return $termo;
            });

            $termosGerados->push($termo);
        }

        if ($termosGerados->isEmpty()) {
            Notification::make()
                ->title('Nenhum bem pendente de assinatura para os complementos selecionados.')
                ->warning()
                ->send();

            return;
        }

        $this->resetTable();

        Notification::make()
            ->title($termosGerados->count() === 1 ? 'Termo assinado com sucesso!' : "{$termosGerados->count()} termos assinados com sucesso!")
            ->body('Termo(s): ' . $termosGerados->map(fn(Termo $termo): string => $termo->termo_completo)->implode(', '))
            ->success()
            ->send();
    }

    private function complementosOptions(): array
    {
        return $this->complementosBase($this->getQuery());
    }

    private function complementosPendentesOptions(): array
    {
        return $this->complementosBase(
            ItemInventario::query()
                ->where('setor', $this->setorAtual)
                ->where('situacao', '<>', self::SITUACAO_A_INVENTARIAR)
                ->whereNull('termo')
        );
    }

    private function complementosBase(Builder $query): array
    {
        return $query
            ->whereNotNull('id_complementosetor')
            ->with('complementoSetorRef')
            ->get(['id_complementosetor', 'complemento_localizado'])
            ->unique('id_complementosetor')
            ->mapWithKeys(fn(ItemInventario $item): array => [
                $item->id_complementosetor => strtoupper(trim((string) ($item->complementoSetorRef?->descricao ?? $item->complemento_localizado))) ?: "Complemento {$item->id_complementosetor}",
            ])
            ->sort()
            ->toArray();
    }

    private function assinaturaTermo(ItemInventario $record): ?string
    {
        $arquivoDigital = $record->termoRef?->arquivoDigital;

        if ((int) ($arquivoDigital?->situacao ?? -1) !== ArquivoDigital::SITUACAO_VALIDADO) {
            return null;
        }

        return collect([
            $arquivoDigital->validadoPor?->name,
            $arquivoDigital->data_validacao?->format('d/m/Y'),
        ])->filter()->implode(' em ') ?: null;
    }

    private function urlTermo(ItemInventario $record): ?string
    {
        if (blank($record->termo)) {
            return null;
        }

        $arquivoDigital = $record->termoRef?->arquivoDigital;

        if (filled($arquivoDigital?->arquivo_digital)) {
            return config('app.egap') . $arquivoDigital->arquivo_digital;
        }

        return route('termo-inventario.imprimir', ['id' => $record->termo]);
    }

    private function getQuery(): Builder
    {
        return ItemInventario::query()
            ->when(
                blank($this->setorAtual),
                fn(Builder $query) => $query->whereKey(-1),
                fn(Builder $query) => $query->where('setor', $this->setorAtual)
            )
            ->where('situacao', '<>', self::SITUACAO_A_INVENTARIAR)
            ->with([
                'complementoSetorRef',
                'termoRef.arquivoDigital.validadoPor',
            ]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
