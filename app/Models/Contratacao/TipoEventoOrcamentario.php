<?php

namespace App\Models\Contratacao;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TipoEventoOrcamentario extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function orcamento()
    {
        return $this->hasMany(OrcamentoEvento::class, 'tipoevento_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('natureza', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'ORCAMENTO_EVENTO');
        });
        static::creating(function (TipoEventoOrcamentario $row) {
            $row->grupo = 'ORCAMENTO_EVENTO';
        });
    }
}
