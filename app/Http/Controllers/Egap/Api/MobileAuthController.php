<?php

namespace App\Http\Controllers\Egap\Api;

use App\Http\Controllers\Controller;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserMobile;
use App\Services\Egap\UsersConnectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request, UsersConnectionService $usersConnectionService): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $mobileUser = $this->attemptLocalAuthentication(
            $credentials['login'],
            $credentials['password'],
            $usersConnectionService,
        );

        if ($mobileUser instanceof JsonResponse) {
            return $mobileUser;
        }

        if ($mobileUser !== null) {
            return $this->successResponse($mobileUser);
        }

        $mobileUser = $this->attemptEgapAuthentication(
            $credentials['login'],
            $credentials['password'],
            $usersConnectionService,
        );

        if ($mobileUser instanceof JsonResponse) {
            return $mobileUser;
        }

        if ($mobileUser !== null) {
            return $this->successResponse($mobileUser);
        }

        return response()->json([
            'message' => 'Credenciais inválidas.',
        ], 401);
    }

    public function me(Request $request, UsersConnectionService $usersConnectionService): JsonResponse
    {
        $mobileUser = $usersConnectionService->findByUser($request->user());

        if (! $mobileUser instanceof UserMobile) {
            return response()->json([
                'message' => 'Usuário sem vinculo valido para acesso mobile.',
            ], 403);
        }

        $user = $mobileUser->toArray();
        unset($user['token']);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    private function attemptLocalAuthentication(
        string $login,
        string $password,
        UsersConnectionService $usersConnectionService,
    ): UserMobile|JsonResponse|null {
        $user = $this->findLocalUserByLogin($login);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $mobileUser = $usersConnectionService->findByUser($user);

        if ($mobileUser === null) {
            return $this->mobileAccessDeniedResponse();
        }

        return $mobileUser;
    }

    private function attemptEgapAuthentication(
        string $login,
        string $password,
        UsersConnectionService $usersConnectionService,
    ): UserMobile|JsonResponse|null {
        $user = $this->findEgapUserByLogin($login);

        if (! $user || $user->block || ! Hash::check($password, $user->password)) {
            return null;
        }

        $mobileUser = $usersConnectionService->findByEgapUser($user);

        if ($mobileUser === null) {
            return $this->mobileAccessDeniedResponse();
        }

        return $mobileUser;
    }

    private function findLocalUserByLogin(string $login): ?User
    {
        $trimmedLogin = trim($login);

        if ($trimmedLogin === '') {
            return null;
        }

        $normalizedCpf = $this->normalizeCpf($trimmedLogin);

        return User::query()
            ->where(function (Builder $query) use ($trimmedLogin, $normalizedCpf): void {
                $query
                    ->where('login', $trimmedLogin)
                    ->orWhere('email', $trimmedLogin);

                if ($normalizedCpf !== '') {
                    $query
                        ->orWhere('cpf', $normalizedCpf)
                        ->orWhereRaw($this->normalizeSqlColumn('cpf').' = ?', [$normalizedCpf]);
                }
            })
            ->first();
    }

    private function findEgapUserByLogin(string $login): ?UserEgap
    {
        $trimmedLogin = trim($login);

        if ($trimmedLogin === '') {
            return null;
        }

        return UserEgap::query()
            ->where(function (Builder $query) use ($trimmedLogin): void {
                $query
                    ->where('username', $trimmedLogin)
                    ->orWhere('email', $trimmedLogin);
            })
            ->first();
    }

    private function normalizeCpf(string $cpf): string
    {
        return preg_replace('/\D+/', '', trim($cpf)) ?? '';
    }

    private function normalizeSqlColumn(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, '.', ''), '-', ''), '/', ''), ' ', ''), ',', '')";
    }

    private function successResponse(UserMobile $user): JsonResponse
    {
        $baseUser = $user->baseUser();

        if (! $baseUser) {
            return response()->json([
                'message' => 'Não foi possível gerar o token de acesso para o usuário mobile.',
            ], 500);
        }

        $token = $baseUser->createToken('mobile-app')->plainTextToken;

        $user->setMobileToken($token);

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => $user->toArray(),
        ]);
    }

    private function mobileAccessDeniedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Usuário sem vínculo válido para acesso mobile.',
        ], 403);
    }
}
