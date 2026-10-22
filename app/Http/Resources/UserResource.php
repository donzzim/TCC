<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @OA\Schema(
 * schema="User",
 * title="User",
 * description="Usuário",
 * @OA\Property(property="id", type="integer", description="ID do usuário"),
 * @OA\Property(property="name", type="string", description="Nome do usuário"),
 * @OA\Property(property="email", type="string", format="email", description="Email do usuário"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Data de atualização")
 * )
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'name' => $this->name,
            'fullname' => $this->fullname,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'telefone' => $this->telefone,
            'perfil' => $this->perfil(),
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y'),
            'updated_at' => Carbon::parse($this->updated_at)->format('d/m/Y'),
        ];
    }
}
