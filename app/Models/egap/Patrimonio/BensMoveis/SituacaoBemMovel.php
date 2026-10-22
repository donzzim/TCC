<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SituacaoBemMovel extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_situacao';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'descricao',
        'Usuario',
        'situacao'
    ];

    /**
     * ✅ ACCESSOR: Formata a exibição como "Descrição/Situação"
     * Ex: "Ativo/Ativo" ou "Doação/Baixado"
     */
    public function getDescricaoCompletaAttribute(): string
    {
        return "{$this->descricao}/{$this->situacao}";
    }

    public function usuarioRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    public function bensMoveis(): HasMany
    {
        return $this->hasMany(BemMovel::class, 'SituacaoBem', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
