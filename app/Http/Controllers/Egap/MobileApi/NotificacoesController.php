<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificacoesController extends Controller
{
    private const POR_PAGINA = 20;

    public function index(MobileEgapSession $sessao): JsonResponse
    {
        $user = $sessao->user();

        $paginador = $user->notifications()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA);

        return response()->json([
            'notificacoes' => $paginador->getCollection()
                ->map(fn (DatabaseNotification $n): array => $this->formatar($n))
                ->values(),
            'meta' => [
                'current_page' => $paginador->currentPage(),
                'per_page' => $paginador->perPage(),
                'total' => $paginador->total(),
                'last_page' => $paginador->lastPage(),
                'from' => $paginador->firstItem(),
                'to' => $paginador->lastItem(),
                'has_more' => $paginador->hasMorePages(),
                'nao_lidas' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function marcarLida(MobileEgapSession $sessao, string $id): Response
    {
        $notificacao = $sessao->user()->notifications()->whereKey($id)->firstOrFail();

        $notificacao->markAsRead();

        return response()->noContent();
    }

    public function marcarTodasLidas(MobileEgapSession $sessao): Response
    {
        $sessao->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatar(DatabaseNotification $notificacao): array
    {
        $dados = (array) $notificacao->data;
        $view = is_array($dados['viewData'] ?? null) ? $dados['viewData'] : [];

        return [
            'id' => (string) $notificacao->id,
            'tipo' => $view['tipo'] ?? null,
            'titulo' => is_string($dados['title'] ?? null) ? $dados['title'] : '',
            'corpo' => $this->textoPuro($dados['body'] ?? null),
            'rota_app' => $view['rota_app'] ?? null,
            'lida' => $notificacao->read_at !== null,
            'criada_em' => $notificacao->created_at?->toIso8601String(),
        ];
    }

    private function textoPuro(mixed $html): string
    {
        if (! is_string($html) || $html === '') {
            return '';
        }

        $texto = preg_replace('#<br\s*/?>#i', "\n", $html);
        $texto = preg_replace('#</p\s*>#i', "\n\n", $texto);
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

        return trim($texto);
    }
}
