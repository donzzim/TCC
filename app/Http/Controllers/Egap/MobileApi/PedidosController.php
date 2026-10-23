<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Egap\MobileApi\CriarPedidoRequest;
use App\Http\Requests\Egap\MobileApi\ListarMateriaisRequest;
use App\Services\Egap\Mobile\PedidosMobileService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PedidosController extends Controller
{
    public function contexto(MobileEgapSession $sessao, PedidosMobileService $pedidosService): JsonResponse
    {
        $scope = $this->scope($sessao);

        return response()->json([
            'scope' => $scope,
            'complementos' => $pedidosService->complementos($scope),
        ]);
    }

    public function materiais(
        ListarMateriaisRequest $request,
        MobileEgapSession $sessao,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $this->scope($sessao);
        $validated = $request->validated();

        $paginator = $pedidosService->materiais(
            scope: $scope,
            tipo: $validated['tipo'],
            search: $validated['search'] ?? null,
            perPage: (int) ($validated['per_page'] ?? 30),
        );

        return response()->json([
            'materiais' => $paginator->items(),
            'meta' => $this->meta($paginator),
        ]);
    }

    public function index(Request $request, MobileEgapSession $sessao, PedidosMobileService $pedidosService): JsonResponse
    {
        $scope = $this->scope($sessao);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $paginator = $pedidosService->pedidosDoUsuario($scope, (int) ($validated['per_page'] ?? 15));

        return response()->json([
            'pedidos' => $paginator->items(),
            'meta' => $this->meta($paginator),
        ]);
    }

    public function store(
        CriarPedidoRequest $request,
        MobileEgapSession $sessao,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $this->scope($sessao);
        $validated = $request->validated();

        $pedido = $pedidosService->criarPedido($scope, $validated);

        return response()->json([
            'message' => 'Pedido cadastrado com sucesso.',
            'pedido' => [
                'id' => (int) $pedido->id,
                'data' => optional($pedido->date_time)->toIso8601String(),
                'tipo' => $validated['tipo'],
                'situacao' => [
                    'id' => $pedido->idSituacao,
                    'descricao' => $pedido->situacao?->Descricao,
                ],
                'itens_total' => $pedido->itens->count(),
            ],
        ], 201);
    }

    /**
     * Escopo vem sempre da sessão mobile; pedidos exigem vínculo EGAP.
     *
     * @return array{user_id:int,id_egap:int,setor:int,unidade_judiciaria:int}
     */
    private function scope(MobileEgapSession $sessao): array
    {
        $scope = $sessao->escopo();

        if (empty($scope['id_egap'])) {
            throw ValidationException::withMessages([
                'user' => 'Usuário sem vinculo EGAP, unidade judiciaria ou setor valido para criar pedidos.',
            ]);
        }

        return $scope;
    }

    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }
}
