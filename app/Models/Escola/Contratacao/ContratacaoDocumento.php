<?php

namespace App\Models\Contratacao;

use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ContratacaoDocumento extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_documentos';
    protected $fillable = [
        'modelable_type',
        'modelable_id',
        'setor_id',
        'modelo_id',
        'atualizado_por',
    ];

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ContratacaoModeloDocumento::class, 'modelo_id', 'id');
    }
    public function tipoprocesso(): BelongsTo
    {
        return $this->belongsTo(TipoProcesso::class, 'modelable_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function modelable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
