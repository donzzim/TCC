<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Egap\MobileApi\DivergenciaRequest;
use App\Http\Requests\Egap\MobileApi\LeituraRequest;
use App\Http\Requests\Egap\MobileApi\NaoLocalizadosRequest;
use App\Services\Egap\Mobile\ConferenciaBensService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConferenciaController extends Controller
{
    public function __construct(private readonly ConferenciaBensService $service) {}

    public function atual(MobileEgapSession $sessao): JsonResponse
    {
        return response()->json($this->service->atual($sessao->escopo()));
    }

    public function bens(Request $request, MobileEgapSession $sessao): JsonResponse
    {
        $status = $request->query('status');

        return response()->json(
            $this->service->bens($sessao->escopo(), is_string($status) ? $status : null, $this->perPage($request)),
        );
    }

    public function leituras(LeituraRequest $request, MobileEgapSession $sessao): JsonResponse
    {
        return response()->json(
            $this->service->registrarLeitura($sessao->escopo(), $request->validated('codigo')),
        );
    }

    public function naoLocalizados(NaoLocalizadosRequest $request, MobileEgapSession $sessao): JsonResponse
    {
        return response()->json(
            $this->service->naoLocalizados(
                $sessao->escopo(),
                $request->validated('bem_ids'),
                $request->validated('justificativa'),
            ),
        );
    }

    public function divergencias(DivergenciaRequest $request, MobileEgapSession $sessao): JsonResponse
    {
        return response()->json(
            $this->service->registrarDivergencia($sessao->escopo(), $request->validated()),
            201,
        );
    }

    public function finalizar(MobileEgapSession $sessao): JsonResponse
    {
        return response()->json($this->service->finalizar($sessao->escopo()));
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 30);

        return $perPage < 1 ? 30 : min($perPage, 50);
    }
}
