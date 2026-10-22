<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Recursos extends Model
{
    protected $table = 'esc_recursos';
    protected $fillable = [
        'atualizado_por',
        'curso_id',
        'tiporecurso_id',
    ];
    protected static function booted(): void
    {
        static::creating(function (Recursos $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (Recursos $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
