<?php

namespace App\Models\Escola;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Documentos extends Model
{
    protected $table = 'esc_documentos';
    protected $fillable = ['user_id', 'tipodocumento_id', 'titulo', 'arquivo'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function tipodocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumentos::class, 'tipodocumento_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (Documentos $row) {
            $row->user_id = Auth::id();
            $row->atualizado_por = Auth::id();
        });

        static::updating(function (Documentos $row) {
            $row->user_id = Auth::id();
            $row->atualizado_por = Auth::id();
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
