<?php

namespace App\Models\Cadastro;

use App\Models\Contratacao\Processo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Fornecedores extends Model
{
    protected $table = 'cad_fornecedores';
    protected $fillable = [
        'cnpj',
        'razao_social',
        'telefone',
        'email',
        'observacao',
    ];
    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class, 'fornecedor_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (Fornecedores $row) {
            // $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (Fornecedores $row) {
            // $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
