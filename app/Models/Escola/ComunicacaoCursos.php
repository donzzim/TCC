<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ComunicacaoCursos extends Model
{
    protected $table = 'esc_comunicacaocurso';
    protected $fillable = [
        'curso_id',
        // 'atividade_id',
        'comunicacao_id',
    ];
    public function comunicacao(): BelongsToMany
    {
        return $this->belongsToMany(Comunicacao::class, 'esc_comunicacaocurso', 'curso_id', 'comunicacao_id');
    }
}
