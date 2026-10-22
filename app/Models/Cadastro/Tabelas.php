<?php

namespace App\Models\Cadastro;

use App\Models\Escola\Acao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tabelas extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
        'json',
    ];
    protected $casts = [
        'json' => 'array',
    ];
    public function acoes(): HasMany
    {
        return $this->hasMany(Acao::class, 'tipoatividade_id', 'id');
    }
    public function situacaoDespesa(): HasMany
    {
        return $this->hasMany(Acao::class, 'situacao_id', 'id');
    }
}
