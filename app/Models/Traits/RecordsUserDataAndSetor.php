<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;


trait RecordsUserDataAndSetor
{

    /**
     * Método de boot específico da Trait.
     */
    protected static function bootRecordsUserDataAndSetor(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                if (Session::has('setorprincipal')) {
                    $model->setor_id = Session::get('setorprincipal');
                } else {
                    $setor = User::find(auth()->id())->setorPrincipal()->first();
                    Session::put('setorprincipal', $setor->id);
                    $model->setor_id = $setor->id;
                }
                $model->atualizado_por = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->atualizado_por = auth()->id();
            }
        });
    }
}
