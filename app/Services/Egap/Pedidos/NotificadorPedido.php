<?php

namespace App\Services\Egap\Pedidos;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Pages\AcompanhamentoDePedidosAlmoxarifado;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\AcompanhamentoDePedidosPatrimonio;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Almoxarifado\SituacaoPedido;
use App\Models\egap\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Avisa o solicitante, no sino do EGAP e no app, de cada atualização do pedido dele.
 *
 * Os observers de Pedidos, ItemPedido e FasePedido chamam registrar*() (já depois do commit); as mudanças
 * ficam juntas por pedido até o fim da requisição, então uma ação que mexe em vários itens gera um aviso só.
 * Quem fez a mudança não é avisado dela (o solicitante cancelando os próprios itens, por exemplo).
 */
class NotificadorPedido
{
    public const TIPO = 'pedido_atualizado';

    private const SETOR_ALMOXARIFADO = 799;

    private const ICONE = 'heroicon-o-shopping-cart';

    /** Cancelado, Invalidado, Suspenso e Devolvido: a observação de quem atendeu vai junto. */
    private const SITUACOES_COM_MOTIVO = [4, 5, 10, 11];

    /**
     * @var array<int, array{
     *     em: CarbonImmutable,
     *     autor: User|null,
     *     criado: bool,
     *     situacao: array{de: int|null, para: int}|null,
     *     itens: array<int, array{de: int|null, para: int|null, atendida_de: int|null}>,
     *     fases: array<int, array{situacao: int|null, descricao: string}>,
     * }>
     */
    private static array $pendentes = [];

    public static function pedidoCriado(Pedidos $pedido): void
    {
        $entrada = &static::entrada((int) $pedido->getKey());
        $entrada['criado'] = true;
    }

    public static function situacaoAlterada(Pedidos $pedido, ?int $de, int $para): void
    {
        $entrada = &static::entrada((int) $pedido->getKey());

        // várias trocas na mesma requisição: vale a primeira origem e o último destino
        $entrada['situacao'] = ['de' => $entrada['situacao']['de'] ?? $de, 'para' => $para];
    }

    public static function itemAlterado(ItemPedido $item, ?int $de, ?int $para, ?int $atendidaDe, bool $observacao): void
    {
        $entrada = &static::entrada((int) $item->idPedido);
        $anterior = $entrada['itens'][(int) $item->getKey()] ?? null;

        $entrada['itens'][(int) $item->getKey()] = [
            'de' => $anterior === null ? $de : $anterior['de'],
            'para' => $para,
            'atendida_de' => $anterior === null ? $atendidaDe : $anterior['atendida_de'],
            'observacao' => $observacao || ($anterior['observacao'] ?? false),
        ];
    }

    public static function faseRegistrada(int $pedidoId, ?int $situacao, ?string $descricao): void
    {
        $descricao = trim((string) $descricao);

        if ($descricao === '') {
            return;
        }

        $entrada = &static::entrada($pedidoId);
        $entrada['fases'][] = ['situacao' => $situacao, 'descricao' => $descricao];
    }

    /**
     * Grava os avisos acumulados. Roda sozinho no fim da requisição; os testes chamam direto.
     *
     * @return int quantas notificações foram gravadas
     */
    public static function enviarPendentes(): int
    {
        $pendentes = static::$pendentes;
        static::$pendentes = [];

        if ($pendentes === []) {
            return 0;
        }

        $pedidos = Pedidos::query()
            ->with(['situacao', 'itens.situacaoRef', 'itens.materialRef', 'itens.descricaoDetalhadaRef'])
            ->whereKey(array_keys($pendentes))
            ->get()
            ->keyBy(fn (Pedidos $p): int => (int) $p->getKey());

        $situacoes = SituacaoPedido::query()->pluck('Descricao', 'id')->map(fn ($d): string => (string) $d)->all();
        $linhas = [];

        foreach ($pendentes as $pedidoId => $entrada) {
            $pedido = $pedidos->get($pedidoId);

            if ($pedido === null) {
                continue;
            }

            $destinatarios = static::destinatarios($pedido);

            // quem mexeu no próprio pedido não precisa ser avisado do que acabou de fazer
            if ($entrada['autor'] !== null && in_array((int) $entrada['autor']->getKey(), $destinatarios, true)) {
                continue;
            }

            if ($destinatarios === []) {
                continue;
            }

            $notificacao = static::montar($pedido, $entrada, $situacoes);

            foreach ($destinatarios as $userId) {
                $linhas[] = static::linha($userId, $notificacao, $entrada['em']);
            }
        }

        if ($linhas !== []) {
            DB::connection((new User)->getConnectionName())->table('notifications')->insert($linhas);
        }

        return count($linhas);
    }

    /** Descarta o que estava acumulado (testes). */
    public static function limpar(): void
    {
        static::$pendentes = [];
    }

    /**
     * Usuários (users.id) do solicitante: os de CPF igual cujo vínculo EGAP (mesmo critério do login) é ele.
     *
     * @return array<int, int>
     */
    public static function destinatarios(Pedidos $pedido): array
    {
        $egapId = (int) $pedido->Solicitante;

        if ($egapId <= 0) {
            return [];
        }

        $formatos = InfoUser::query()
            ->where('usuario_id', $egapId)
            ->pluck('cpf')
            ->flatMap(fn ($cpf): array => EgapSession::formatosDeBusca($cpf))
            ->unique()
            ->values()
            ->all();

        if ($formatos === []) {
            return [];
        }

        return User::query()
            ->where('ativo', 1)
            ->whereIn('cpf', $formatos)
            ->get(['id', 'cpf'])
            ->filter(fn (User $user): bool => EgapSession::egapIdPorCpf($user->cpf) === $egapId)
            ->map(fn (User $user): int => (int) $user->getKey())
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function &entrada(int $pedidoId): array
    {
        if (! isset(static::$pendentes[$pedidoId])) {
            static::$pendentes[$pedidoId] = [
                'em' => CarbonImmutable::now(),
                'autor' => static::autor(),
                'criado' => false,
                'situacao' => null,
                'itens' => [],
                'fases' => [],
            ];

            // nomeado: várias chamadas na mesma requisição deixam um único envio no fim dela
            defer(fn () => static::enviarPendentes(), 'notificador-pedido');
        }

        return static::$pendentes[$pedidoId];
    }

    private static function autor(): ?User
    {
        try {
            $user = auth()->user();
        } catch (Throwable) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @param  array<int, string>  $situacoes
     */
    private static function montar(Pedidos $pedido, array $entrada, array $situacoes): Notification
    {
        $nome = fn (?int $id): string => $id === null ? 'sem situação' : ($situacoes[$id] ?? "situação {$id}");
        $numero = "Pedido nº {$pedido->getKey()}";
        $paragrafos = [];

        $quando = 'em '.$entrada['em']->format('d/m/Y').' às '.$entrada['em']->format('H:i');
        $por = $entrada['autor']?->name ? ' por '.e($entrada['autor']->name) : '';
        $paragrafos[] = ($entrada['criado'] ? 'Registrado em seu nome ' : 'Atualizado ').$quando.$por.'.';

        if ($entrada['situacao'] !== null && $entrada['situacao']['de'] !== $entrada['situacao']['para']) {
            $paragrafos[] = 'Situação: '.e($nome($entrada['situacao']['de'])).' → <strong>'.e($nome($entrada['situacao']['para'])).'</strong>';
        } elseif ($entrada['criado'] || $entrada['situacao'] !== null) {
            $paragrafos[] = 'Situação: <strong>'.e($pedido->situacao?->Descricao ?? $nome((int) $pedido->idSituacao)).'</strong>';
        }

        $itens = $pedido->itens->keyBy(fn (ItemPedido $i): int => (int) $i->getKey());
        $linhasItens = [];
        $justificativas = [];

        foreach ($entrada['itens'] as $itemId => $mudanca) {
            $item = $itens->get($itemId);

            if ($item === null) {
                continue;
            }

            $texto = '• '.e($item->material_nome ?? "Item #{$itemId}").' ('.(int) $item->QuantidadeMaterial.')';

            if ($mudanca['de'] !== $mudanca['para']) {
                $texto .= ': '.e($nome($mudanca['de'])).' → '.e($nome($mudanca['para']));
            } else {
                $texto .= ': '.e($nome($mudanca['para']));
            }

            if ($mudanca['atendida_de'] !== null && (int) $item->QuantidadeMaterialAtendida !== $mudanca['atendida_de']) {
                $texto .= ' — atendidos '.(int) $item->QuantidadeMaterialAtendida.' de '.(int) $item->QuantidadeMaterial;
            }

            $linhasItens[] = $texto;

            // a observação é de quem atende (o solicitante escreve a dele na justificativa)
            $observacao = trim((string) $item->ObservacaoItem);

            if ($observacao !== '' && ($mudanca['observacao'] || in_array($mudanca['para'], self::SITUACOES_COM_MOTIVO, true))) {
                $justificativas[] = e($item->material_nome ?? "Item #{$itemId}").': '.e($observacao);
            }
        }

        if ($entrada['criado'] && $linhasItens === []) {
            $linhasItens = $pedido->itens
                ->map(fn (ItemPedido $i): string => '• '.e($i->material_nome ?? "Item #{$i->getKey()}").' ('.(int) $i->QuantidadeMaterial.')')
                ->all();
        }

        if ($linhasItens !== []) {
            $paragrafos[] = (count($linhasItens) === 1 ? 'Item:' : 'Itens:').'<br>'.implode('<br>', $linhasItens);
        }

        if ($justificativas !== []) {
            $paragrafos[] = 'Observação:<br>'.implode('<br>', $justificativas);
        }

        $fases = collect($entrada['fases'])->pluck('descricao')->unique()->values();

        if ($fases->isNotEmpty()) {
            $paragrafos[] = 'Descrição: '.$fases->map(fn (string $d): string => e($d))->implode('<br>');
        }

        $url = (int) $pedido->setor_responsavel === self::SETOR_ALMOXARIFADO
            ? AcompanhamentoDePedidosAlmoxarifado::getUrl(panel: 'egap')
            : AcompanhamentoDePedidosPatrimonio::getUrl(panel: 'egap');

        return Notification::make()
            ->title($numero.': '.static::resumo($entrada, $nome))
            ->body('<p>'.implode('</p><p>', $paragrafos).'</p>')
            ->icon(self::ICONE)
            ->actions([
                Action::make('pedido')
                    ->label('Acompanhar pedido')
                    ->url($url),
            ])
            ->viewData([
                'tipo' => self::TIPO,
                'pedido_id' => (int) $pedido->getKey(),
                'rota_app' => '/pedidos/'.$pedido->getKey(),
            ]);
    }

    /**
     * O que aparece no título: a nova situação do pedido, ou a dos itens quando todos foram para a mesma.
     *
     * @param  array<string, mixed>  $entrada
     */
    private static function resumo(array $entrada, callable $nome): string
    {
        if ($entrada['criado']) {
            return 'registrado em seu nome';
        }

        if ($entrada['situacao'] !== null && $entrada['situacao']['de'] !== $entrada['situacao']['para']) {
            return $nome($entrada['situacao']['para']);
        }

        $destinos = collect($entrada['itens'])->filter(fn (array $m): bool => $m['de'] !== $m['para'])->pluck('para')->unique();

        if ($destinos->count() === 1) {
            $qtd = count($entrada['itens']);

            return ($qtd === 1 ? 'item ' : "{$qtd} itens ").mb_strtolower($nome($destinos->first()));
        }

        $situacaoFase = collect($entrada['fases'])->pluck('situacao')->filter()->last();

        return $situacaoFase !== null && $entrada['itens'] === [] ? $nome((int) $situacaoFase) : 'atualizado';
    }

    /**
     * Mesma linha que o canal "database" grava para o sendToDatabase() do Filament, gravada já
     * (o sendToDatabase() enfileira e não há worker) — ver NotificadorInventario.
     *
     * @return array<string, mixed>
     */
    private static function linha(int $userId, Notification $notificacao, CarbonImmutable $em): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => DatabaseNotification::class,
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => $userId,
            'data' => json_encode($notificacao->getDatabaseMessage()),
            'read_at' => null,
            'created_at' => $em,
            'updated_at' => $em,
        ];
    }
}
