<?php

namespace App\Services\Pedagogico;



use App\Models\Escola\Instrutores;
use Illuminate\Database\Eloquent\Model;

class InstrutorService
{
    public static function createOrUpdateFromArray(array $data, $curso = null)
    {
        $instrutores = $data['instrutores'] ?? [];

        if (!isset($data['id'])) {
            $data['id'] = $curso->id ?? null;
        }

        foreach ($instrutores as $instrutor) {

            if (isset($instrutor['id'])) {

                $existingInstrutor = Instrutores::find($instrutor['id']);

                if ($existingInstrutor) {

                    $existingInstrutor->update($instrutor);
                }
            } else {

                $instrutor['curso_id'] = $data['id'];
                // dd($instrutor);

                Instrutores::create($instrutor);
            }
        }

        if (isset($data['id'])) {
            $novosInstrutoresIds = collect($instrutores)->pluck('id')->filter()->toArray();
            Instrutores::where('curso_id', $data['id'])
                ->whereNotIn('id', $novosInstrutoresIds)
                ->each(function ($instrutor) {
                    $instrutor->delete();
                });
        }
    }
}
