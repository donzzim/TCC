<?php

namespace App\Models\Escola;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @property \Carbon\Carbon|null $created_at
 * @property string|null $justificativa
 * @property string|null $objetivo_geral
 * @property string|null $conteudo_programatico
 * @property string|null $abordagem_pedagogica
 * @property string|null $avaliacao_cursistas
 * @property string|null $referencias_bibliograficas
 * @property string|null $curriculo_resumido
 */
class CursoComentarios extends Model
{
    protected $table = 'esc_cursocomentarios';

    protected $fillable = [
        'curso_id',
        'atualizado_por',
        'descricao',
        'justificativa',
        'objetivo_geral',
        'conteudo_programatico',
        'abordagem_pedagogica',
        'avaliacao_cursistas',
        'referencias_bibliograficas',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    protected static function booted(): void
    {
        static::creating(function (CursoComentarios $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (CursoComentarios $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
