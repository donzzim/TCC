<?php

namespace App\Models\Escola;

use App\Models\Escola\Cursos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $table = 'esc_formsubmissions';
    protected $fillable = [
        'curso_id',
        'form_id',
        'user_id',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionField::class);
    }
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }
    public function form(): BelongsTo
    {
        return $this->belongsTo(FormAvaliacao::class, 'form_id', 'id');
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (FormSubmission $row) {
            // $row->curso_id = $row->atividade->id;
            // $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (FormSubmission $row) {
            // $row->atualizado_por = Auth::user()->id;
        });

        // static::deleted(function (Transacao $transacao){
        //                 if($transacao->data_pagto){
        //         static::atualizarSaldo($transacao);
        //     }
        // });
    }
}
