<?php

namespace App\Http\Controllers\Egap\Api;

use App\Http\Controllers\Controller;
use App\Models\egap\UserMobile;
use App\Services\Egap\Mobile\PedidosMobileService;
use App\Services\Egap\UsersConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PedidosController extends Controller
{
    public function contexto(
        Request $request,
        UsersConnectionService $usersConnectionService,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $this->scope($request, $usersConnectionService, $pedidosService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        return response()->json([
            'scope' => $scope,
            'complementos' => $pedidosService->complementos($scope),
        ]);
    }

    public function materiais(
        Request $request,
        UsersConnectionService $usersConnectionService,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $this->scope($request, $usersConnectionService, $pedidosService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $validated = $request->validate([
            'tipo' => ['required', 'in:consumo,permanente'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $paginator = $pedidosService->materiais(
            scope: $scope,
            tipo: $validated['tipo'],
            search: $validated['search'] ?? null,
            perPage: (int) ($validated['per_page'] ?? 30),
        );

        return response()->json([
            'materiais' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function index(
        Request $request,
        UsersConnectionService $usersConnectionService,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $this->scope($request, $usersConnectionService, $pedidosService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $paginator = $pedidosService->pedidosDoUsuario($scope, (int) ($validated['per_page'] ?? 15));

        return response()->json([
            'pedidos' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function store(
        Request $request,
        UsersConnectionService $usersConnectionService,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $this->scope($request, $usersConnectionService, $pedidosService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $validated = $request->validate([
            'tipo' => ['required', 'in:consumo,permanente'],
            'complemento_setor_id' => ['required', 'integer'],
            'justificativa' => ['nullable', 'string', 'max:500'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.material_id' => ['required', 'integer'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1', 'max:5000'],
            'itens.*.tipo_atendimento' => ['nullable', 'in:adicao,substituicao'],
            'itens.*.justificativa' => ['nullable', 'string', 'max:500'],
            'itens.*.patrimonio_substituido' => ['nullable', 'string', 'max:120'],
        ]);

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
     * @return array{user_id:int,id_egap:int,setor:int,unidade_judiciaria:int}|JsonResponse
     */
    private function scope(
        Request $request,
        UsersConnectionService $usersConnectionService,
        PedidosMobileService $pedidosService,
    ): array|JsonResponse {
        $mobileUser = $usersConnectionService->findByUser($request->user());

        if (! $mobileUser instanceof UserMobile) {
            return response()->json([
                'message' => 'Usuário sem vinculo valido para pedidos mobile.',
            ], 403);
        }

        try {
            return $pedidosService->resolveScope($mobileUser);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?: 'Usuario sem escopo valido para pedidos.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }
}
