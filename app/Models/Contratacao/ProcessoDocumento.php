<?php

namespace App\Models\Contratacao;

use App\Models\Escola\Instrutores;
use App\Models\Escola\Documentos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ProcessoDocumento extends Model
{
    protected $table = 'cnt_processodocumentos';

    protected $fillable = [
        'instrutor_id',
        'modelo_id',
        'documento_id',
        'conteudo_gerado',
        'is_assinado',
        'assinado_em',
        'assinado_ip',
        'status_aprovacao',
        'motivo_rejeicao',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'is_assinado' => 'boolean',
            'assinado_em' => 'datetime',
        ];
    }

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Instrutores::class, 'instrutor_id');
    }
    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ContratacaoModeloDocumento::class, 'modelo_id');
    }
    public function arquivoPessoal(): BelongsTo
    {
        return $this->belongsTo(Documentos::class, 'documento_id');
    }

    protected static function booted(): void
    {
        static::creating(fn($model) => $model->atualizado_por = Auth::id());
        static::updating(fn($model) => $model->atualizado_por = Auth::id());
    }
}
