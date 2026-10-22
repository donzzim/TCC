<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Http\Controllers\Controller;
use App\Models\egap\UserMobile;
use App\Services\Egap\Mobile\ConferenciaBensService;
use App\Services\Egap\UsersConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConferenciaBensController extends Controller
{
    public function __construct(
        private readonly ConferenciaBensService $conferenciaBensService,
        private readonly UsersConnectionService $usersConnectionService,
    ) {}

    public function atual(Request $request): JsonResponse
    {
        return response()->json(
            $this->conferenciaBensService->atual($this->scope($request)),
        );
    }

    public function bens(Request $request): JsonResponse
    {
        $status = $request->query('status');

        return response()->json(
            $this->conferenciaBensService->bens(
                $this->scope($request),
                is_string($status) ? $status : null,
                $this->perPage($request),
            ),
        );
    }

    public function validarLeitura(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string'],
        ]);

        return response()->json(
            $this->conferenciaBensService->validarLeitura($this->scope($request), $data['codigo']),
        );
    }

    public function localizar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bem_id' => ['nullable', 'integer'],
            'codigo' => ['nullable', 'string'],
        ]);

        if (empty($data['bem_id']) && empty($data['codigo'])) {
            return response()->json([
                'message' => 'Informe o bem ou o código patrimonial.',
            ], 422);
        }

        return response()->json(
            $this->conferenciaBensService->localizar($this->scope($request), $data),
            201,
        );
    }

    public function naoLocalizados(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bens' => ['required', 'array', 'min:1'],
            'bens.*' => ['integer'],
            'justificativa' => ['required', 'string'],
        ]);

        return response()->json(
            $this->conferenciaBensService->naoLocalizados(
                $this->scope($request),
                $data['bens'],
                $data['justificativa'],
            ),
        );
    }

    public function divergencias(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bem_id' => ['nullable', 'integer'],
            'codigo' => ['nullable', 'string'],
            'campos' => ['nullable', 'array'],
            'campos.*' => ['string'],
            'observacao' => ['required', 'string'],
        ]);

        if (empty($data['bem_id']) && empty($data['codigo'])) {
            return response()->json([
                'message' => 'Informe o bem ou o código patrimonial.',
            ], 422);
        }

        return response()->json(
            $this->conferenciaBensService->registrarDivergencia($this->scope($request), $data),
            201,
        );
    }

    public function finalizar(Request $request): JsonResponse
    {
        return response()->json(
            $this->conferenciaBensService->finalizar($this->scope($request)),
        );
    }

    private function scope(Request $request): array
    {
        $mobileUser = $this->usersConnectionService->findByUser($request->user());

        if (! $mobileUser instanceof UserMobile) {
            abort(403, 'Usuário sem vínculo válido para conferência.');
        }

        $setor = $this->filledInteger($mobileUser->setor());
        $unidadeJudiciaria = $this->filledInteger($mobileUser->unidadeJudiciaria());
        $idEgap = $this->filledInteger($mobileUser->idEgap());

        if ($setor === null || $unidadeJudiciaria === null || $idEgap === null) {
            abort(422, 'Usuário sem lotação válida para conferência.');
        }

        return [
            'user_id' => $mobileUser->id(),
            'id_egap' => $idEgap,
            'setor' => $setor,
            'unidade_judiciaria' => $unidadeJudiciaria,
        ];
    }

    private function filledInteger(int|string|null $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $integerValue = (int) $value;

        return $integerValue > 0 ? $integerValue : null;
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 30);

        if ($perPage < 1) {
            return 30;
        }

        return min($perPage, 50);
    }
}
