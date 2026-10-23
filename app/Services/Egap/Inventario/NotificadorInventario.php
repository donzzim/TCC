<?php

namespace App\Services\Egap\Inventario;

use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\BensNoSetor;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Avisos do inventário no sino do EGAP (e, pela mesma tabela, no app).
 *
 * Cada aviso leva em viewData uma `chave`; quem já tem notificação com a mesma chave não recebe de novo.
 *
 * O sino do Filament mostra o corpo como HTML sanitizado (não interpreta Markdown nem quebras de linha),
 * então o corpo é montado com <p> e <br>; a API do app converte para texto puro.
 */
class NotificadorInventario
{
    private const LOTE = 500;

    private const ICONE = 'heroicon-o-clipboard-document-check';

    /**
     * @return int quantos usuários foram notificados
     */
    public static function notificarAbertura(Inventario $inventario): int
    {
        $cobertos = AbrangenciaInventario::setoresCobertos($inventario);

        if ($cobertos->isEmpty()) {
            return 0;
        }

        $destinatarios = DestinatariosInventario::porSetor($cobertos->keys());

        if ($destinatarios->isEmpty()) {
            return 0;
        }

        $nomes = Setores::query()->whereIn('id', $destinatarios->flatten()->unique()->values()->all())->pluck('setor', 'id');
        $numero = "{$inventario->num_inventario}/{$inventario->ano_inventario}";

        return static::enviar(
            chave: "inventario_aberto:{$inventario->id}",
            tipo: 'inventario_aberto',
            rotaApp: '/tutorial',
            destinatarios: $destinatarios,
            montar: function (array $setorIds) use ($cobertos, $nomes, $numero): array {
                $prazos = collect($setorIds)
                    ->map(fn (int $id): array => ['setor' => (string) ($nomes[$id] ?? "Setor {$id}"), 'prazo' => $cobertos[$id]['prazo'] ?? null])
                    ->sortBy(fn (array $linha): string => ($linha['prazo']?->format('Ymd') ?? '99999999').$linha['setor'])
                    ->values();

                $maisProximo = $prazos->pluck('prazo')->filter()->first();

                $linhas = $prazos->map(fn (array $linha): string => e($linha['setor']).': '.static::textoPrazo($linha['prazo']));

                $passos = implode('<br>', [
                    '1. Abra "Bens no Setor (Inventário)" no EGAP ou o app E-Gap Mobile.',
                    '2. Leia a plaqueta de cada bem (ou busque pelo número) para marcá-lo como localizado.',
                    '3. Marque como não localizados os que não encontrar e registre divergências.',
                    '4. Ao terminar, use "Finalizar setor".',
                ]);

                return [
                    'titulo' => "Inventário {$numero} em andamento — Prazo: ".($maisProximo?->format('d/m/Y') ?? 'não definido'),
                    'corpo' => '<p>'.$linhas->implode('<br>').'</p><p>Como fazer a conferência:<br>'.$passos.'</p>',
                ];
            },
        );
    }

    private static function textoPrazo(?CarbonImmutable $prazo): string
    {
        return $prazo === null ? 'sem prazo definido' : 'até '.$prazo->format('d/m/Y');
    }

    /**
     * Envia, em lotes, a quem ainda não tem notificação com esta chave.
     *
     * @param  Collection<int, array<int, int>>  $destinatarios  users.id => setores
     * @param  callable(array<int, int>): array{titulo: string, corpo: string}  $montar
     */
    private static function enviar(string $chave, string $tipo, string $rotaApp, Collection $destinatarios, callable $montar): int
    {
        $jaAvisados = array_flip(static::usuariosComChave($chave));
        $pendentes = $destinatarios->reject(fn (array $setores, int $userId): bool => isset($jaAvisados[$userId]));
        $url = BensNoSetor::getUrl(panel: 'egap');
        $enviados = 0;

        foreach ($pendentes->chunk(self::LOTE, preserveKeys: true) as $lote) {
            $usuarios = User::query()->whereIn('id', $lote->keys()->all())->get()->keyBy('id');

            foreach ($lote as $userId => $setores) {
                $user = $usuarios->get($userId);

                if ($user === null) {
                    continue;
                }

                $texto = $montar($setores);

                Notification::make()
                    ->title($texto['titulo'])
                    ->body($texto['corpo'])
                    ->icon(self::ICONE)
                    ->actions([
                        Action::make('conferencia')
                            ->label('Ir para a conferência')
                            ->url($url),
                    ])
                    ->viewData(['chave' => $chave, 'tipo' => $tipo, 'rota_app' => $rotaApp])
                    ->sendToDatabase($user);

                $enviados++;
            }
        }

        return $enviados;
    }

    /** @return array<int, int> */
    private static function usuariosComChave(string $chave): array
    {
        return DB::connection((new User)->getConnectionName())
            ->table('notifications')
            ->where('notifiable_type', (new User)->getMorphClass())
            ->where('data->viewData->chave', $chave)
            ->pluck('notifiable_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
