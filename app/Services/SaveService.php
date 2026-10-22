<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class SaveService
{

    public static function mergeData(Model $record, array $form_salvo, array $form_original): array
    {

        $dataAtual = $record->fresh()->toArray();

        $alteracoes = array_diff_assoc($form_salvo, $form_original);


        if (empty($alteracoes)) {
            return $form_salvo;
        }

        $data_misturada = array_merge($dataAtual, $alteracoes);

        return $data_misturada;
    }
}
