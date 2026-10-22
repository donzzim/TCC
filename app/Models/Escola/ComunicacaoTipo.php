<?php

namespace App\Models\Escola;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class ComunicacaoTipo extends Model
{
    protected $table = 'esc_comunicacaotipo';
    protected $fillable = [
        'atualizado_por',
        'titulo',
        'texto',
    ];
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function comunicado(): HasMany
    {
        return $this->hasMany(Comunicacao::class, 'tipocomunicacao_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (ComunicacaoTipo $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (ComunicacaoTipo $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
