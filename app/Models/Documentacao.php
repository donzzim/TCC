<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documentacao extends Model
{
    protected $table = 'documentacao';
    protected $fillable = [
        'rota',
        'titulo',
        'conteudo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function historicos(): HasMany
    {
        return $this->hasMany(DocumentacaoHistorico::class)->latest();
    }

    public function ultimaAtualizacao()
    {
        return $this->historicos()->with('user')->first();
    }
}
