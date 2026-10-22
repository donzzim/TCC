<?php

namespace App\Models\Escola;

use App\Models\Escola\ComunicacaoTipo;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Cadastro\Tabelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class Comunicacao extends Model
{
    protected $table = 'esc_comunicacao';
    protected $fillable = [
        'uuid',
        'atualizado_por',
        'tipocomunicacao_id',
        'meiocomunicacao_id',
        'publico_id',
        'texto',
    ];
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(ComunicacaoTipo::class, 'tipocomunicacao_id', 'id');
    }
    public function meio(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'meiocomunicacao_id', 'id');
    }
    public function publico(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'publico_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Cursos::class, 'esc_comunicacaocurso', 'comunicacao_id', 'curso_id', 'id', 'id')
            ->withTimestamps()
            ->select('esc_cursos.*');
    }
    public function atividades(): BelongsToMany
    {
        return $this->belongsToMany(CursoAtividades::class, 'esc_comunicacaocurso', 'comunicacao_id', 'atividade_id');
    }
    protected static function booted(): void
    {
        static::creating(function (Comunicacao $row) {
            $row->uuid = Uuid::uuid4();
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (Comunicacao $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
