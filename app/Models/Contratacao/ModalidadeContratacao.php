<?php

namespace App\Models\Contratacao;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ModalidadeContratacao extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function processos(): HasMany
    {
        return $this->HasMany(Processo::class, 'tipoprocesso_id', 'id');
    }
    public function documentos(): MorphMany
    {
        return $this->morphMany(ContratacaoDocumento::class, 'modelable');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('modalidadecontratacao', function (Builder $builder) {
            $builder->where('grupo', '=', 'MODALIDADE_CONTRATACAO');
        });
        static::creating(function (TipoProcessoEvento $row) {
            $row->grupo = 'MODALIDADE_CONTRATACAO';
        });
    }
}
