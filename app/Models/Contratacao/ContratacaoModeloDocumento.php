<?php

namespace App\Models\Contratacao;

use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class ContratacaoModeloDocumento extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_modelodocumentos';
    protected $fillable = [
        'setor_id',
        'nome',
        'contexto',
        'conteudo',
        'atualizado_por',
    ];
    public function documentos(): HasMany
    {
        return $this->hasMany(ContratacaoDocumento::class, 'modelo_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
