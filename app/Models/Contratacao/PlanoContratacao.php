<?php

namespace App\Models\Contratacao;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PlanoContratacao extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_planocontratacao';
    protected $fillable = [
        'setor_id',
        'solicitante_id',
        'atualizado_por',
        'unidade_gestora',
        'classificacao',
        'comentario',
        'preco_total',
        'ano_execucao',
    ];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function bemServicos(): HasMany
    {
        return $this->hasMany(ContratacaoBemServico::class, 'planocontratacao_id', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
