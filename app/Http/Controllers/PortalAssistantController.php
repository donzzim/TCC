<?php

namespace App\Http\Controllers;

use App\Services\Portal\PortalAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalAssistantController extends Controller
{
    public function __invoke(Request $request, PortalAssistantService $assistant): JsonResponse
    {
        $request->merge([
            'pergunta' => $request->input('pergunta', $request->input('question')),
        ]);

        $data = $request->validate([
            'pergunta' => ['required', 'string', 'min:2', 'max:300'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        return response()->json(
            $assistant->search($data['pergunta'], (int) ($data['limit'] ?? 5))
        );
    }
}
