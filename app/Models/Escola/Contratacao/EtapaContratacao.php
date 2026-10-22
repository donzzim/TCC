<?php

namespace App\Models\Contratacao;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EtapaContratacao extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function bemservicos(): HasMany
    {
        return $this->HasMany(ContratacaoBemServico::class, 'etapa_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('etapacontratacao', function (Builder $builder) {
            $builder->where('grupo', '=', 'ETAPA_PCA');
        });
        static::creating(function (EtapaContratacao $row) {
            $row->grupo = 'ETAPA_PCA';
        });
    }
}
