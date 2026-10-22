<?php

namespace App\Models\Contratacao;

use Illuminate\Database\Eloquent\Model;

class ContratacaoVariavelModelo extends Model
{
    protected $table = 'cnt_variavelmodelos';
    protected $fillable = [
        'variavel',
        'descricao',
        'contexto',
        'texto_padrao',
    ];
}
