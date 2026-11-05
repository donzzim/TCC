<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Monitoramento\Logs\EntradaDeLog;
use App\Monitoramento\Logs\LeitorDeLog;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LogTable extends TabelaDoMonitoramento
{
    private const NIVEIS = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    protected ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $leitor = new LeitorDeLog;
        $arquivos = $leitor->arquivos();

        return $table
            ->heading('Log da aplicação')
            ->description('Últimas 200 entradas dos últimos 2 MB do arquivo escolhido. CPF, tokens e senhas aparecem mascarados.')
            ->records(function (?array $filters, ?string $search) use ($leitor, $arquivos): array {
                $escolhido = $filters['arquivo']['value'] ?? null;
                $arquivo = $escolhido !== null && $leitor->permitido($escolhido) ? $escolhido : ($arquivos[0] ?? null);

                if ($arquivo === null) {
                    return [];
                }

                $entradas = $leitor->ultimas($arquivo, nivel: $filters['nivel']['value'] ?? null, texto: $search, limite: 200);

                return array_map(fn (EntradaDeLog $e, int $i): array => [
                    '__key' => (string) $i,
                    'data' => $e->data,
                    'nivel' => $e->nivel,
                    'mensagem' => mb_strimwidth($e->mensagem, 0, 400, '...'),
                    'completa' => $e->mensagem,
                    'contexto' => $e->contexto === null ? '' : json_encode($e->contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'trace' => $e->trace,
                ], $entradas, array_keys($entradas));
            })
            ->searchable()
            ->filters([
                SelectFilter::make('arquivo')
                    ->label('Arquivo')
                    ->options(array_combine($arquivos, array_map('basename', $arquivos)) ?: [])
                    ->placeholder('Mais recente'),
                SelectFilter::make('nivel')
                    ->label('Nível')
                    ->options(array_combine(self::NIVEIS, self::NIVEIS)),
            ])
            ->columns([
                TextColumn::make('data')->label('Quando')->size('sm'),
                TextColumn::make('nivel')->label('Nível')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'danger',
                        'WARNING' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('mensagem')->label('Mensagem')->wrap()->size('sm'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (array $record) => $record['nivel'].' · '.$record['data'])
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(fn (array $record) => new HtmlString(
                        '<div class="flex flex-col gap-3 text-sm">'
                        .'<p class="break-words">'.e($record['completa']).'</p>'
                        .($record['contexto'] !== '' ? '<pre class="max-h-72 overflow-auto rounded bg-gray-50 p-2 text-xs dark:bg-white/5">'.e($record['contexto']).'</pre>' : '')
                        .($record['trace'] !== '' ? '<pre class="max-h-96 overflow-auto rounded bg-gray-50 p-2 text-xs dark:bg-white/5">'.e($record['trace']).'</pre>' : '')
                        .'</div>'
                    )),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nenhuma entrada encontrada.');
    }
}
