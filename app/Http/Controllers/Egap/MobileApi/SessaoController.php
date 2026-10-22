<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Egap\MobileApi\TrocarSetorRequest;
use App\Http\Resources\Egap\MobileApi\SessaoResource;
use Illuminate\Http\JsonResponse;

class SessaoController extends Controller
{
    public function show(MobileEgapSession $sessao): SessaoResource
    {
        return new SessaoResource($sessao);
    }

    public function trocarSetor(TrocarSetorRequest $request, MobileEgapSession $sessao): SessaoResource
    {
        $sessao->trocarSetor((int) $request->validated('setor_id'));

        return new SessaoResource($sessao);
    }

    public function logout(MobileEgapSession $sessao): JsonResponse
    {
        $sessao->encerrar();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
