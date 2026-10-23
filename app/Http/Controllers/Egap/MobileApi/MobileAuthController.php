<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Egap\MobileApi\LoginRequest;
use App\Http\Resources\Egap\MobileApi\SessaoResource;
use App\Models\egap\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MobileAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim($request->validated('login'));
        $provider = Auth::guard(User::GUARD)->getProvider();

        // Mesmo mapeamento de LoginEgap::getCredentialsFromFormData.
        $campo = match (true) {
            (bool) filter_var($login, FILTER_VALIDATE_EMAIL) => 'email',
            is_numeric($login) => 'cpf',
            default => 'login',
        };

        $password = $request->validated('password');
        $user = $provider->retrieveByCredentials([$campo => $login, 'password' => $password]);

        if (! $user instanceof User || ! $provider->validateCredentials($user, ['password' => $password])) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        if (! $user->ativo || ! ($user->can('egap_access') || $user->isAdminEgap())) {
            return response()->json([
                'message' => 'Usuário sem acesso ao aplicativo.',
                'code' => 'sem_acesso',
            ], 403);
        }

        $token = $user->createToken('mobile-app');
        $sessao = MobileEgapSession::iniciar($user, $token->accessToken, $request->validated('dispositivo'));

        return response()->json([
            'token' => $token->plainTextToken,
            'sessao' => new SessaoResource($sessao),
        ]);
    }
}
