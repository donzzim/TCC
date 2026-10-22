<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @OA\Get(
     * path="/api/v1/users",
     * tags={"Users"},
     * summary="Lista todos os usuários",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Sucesso", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))),
     * @OA\Response(response=401, description="Não autorizado")
     * )
     */
    public function index()
    {
        // $users = User::where('id', '<=', 10)->get();
        $users = User::find(1);
        $users = new UserResource($users);
        return $users;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     * path="/api/v1/users/{id}",
     * tags={"Users"},
     * summary="Pesquisa o Usuário pelo ID",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Sucesso", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))),
     * @OA\Response(response=401, description="Não autorizado")
     * )
     */
    public function show(string $id)
    {
        $users = User::find($id);
        $users = new UserResource($users);
        return $users;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
