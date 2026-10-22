<?php

namespace App\Models\Contratacao;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlanoOrcamentario extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function orcamento()
    {
        return $this->hasMany(Orcamento::class, 'planoorcamentario_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('planoorcamentario', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'PLANO_ORCAMENTARIO');
        });
        static::creating(function (PlanoOrcamentario $row) {
            $row->grupo = 'PLANO_ORCAMENTARIO';
        });
    }
}
