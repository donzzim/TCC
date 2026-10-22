<?php

namespace App\Services\Pedagogico;



use App\Models\Escola\CursoAtividades;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AtividadeService
{
    public static function createOrUpdateFromArray(array $data, $curso = null)
    {

        Log::info('AtividadeService::createOrUpdateFromArray chamado com dados:', $data);


        $atividades = $data['atividades'] ?? [];

        foreach ($atividades as $atividade) {

            if (isset($atividade['id'])) {
                Log::info('Atualizando atividade com ID: ' . $atividade['id']);

                $existingAtividade = CursoAtividades::find($atividade['id']);

                if ($existingAtividade) {

                    $existingAtividade->update($atividade);
                }
            } else {
                Log::info('Criando nova atividade para o curso ID: ' . ($data['id'] ?? ($curso ? $curso->id : 'desconhecido')));
                $atividade['curso_id'] = $data['id'] ?? $curso->id;
                // dd($atividade);

                CursoAtividades::create([
                    'titulo' => $atividade['titulo'],
                    'aprovacao' => $atividade['aprovacao'],
                    'carga_horaria' => $atividade['carga_horaria'],
                    'curso_id' => $atividade['curso_id'],
                    'inicio' => $atividade['inicio'],
                    'termino' => $atividade['termino'],
                    'certificavel' => $atividade['certificavel'],
                    'tipolocal_id' => $atividade['tipolocal_id'],
                ]);
            }
        }
        if (isset($data['id'])) {
            $novasAtividadesIds = collect($atividades)->pluck('id')->toArray();
            // dd(CursoAtividades::where('curso_id', $data['id'])
            //     ->whereNotIn('id', $novasAtividadesIds)
            //     ->get());
            CursoAtividades::where('curso_id', $data['id'])
                ->whereNotIn('id', $novasAtividadesIds)
                ->each(function ($atividade) {
                    $atividade->delete();
                });
        }
    }
}
