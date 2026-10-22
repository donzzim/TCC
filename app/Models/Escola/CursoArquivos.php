<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CursoArquivos extends Model
{
    protected $table = 'esc_cursoarquivos';
    protected $fillable = [
        'curso_id',
        'atualizado_por',
        'descricao',
        'arquivo',
    ];
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (CursoArquivos $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (CursoArquivos $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
