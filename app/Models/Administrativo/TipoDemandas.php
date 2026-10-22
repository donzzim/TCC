<?php

namespace App\Models\Administrativo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDemandas extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function demandas(): HasMany
    {
        return $this->hasMany(Demanda::class, 'tipodemanda_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tiporecurso', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'TIPO_DEMANDA');
        });
        static::creating(function (TipoDemandas $row) {
            $row->grupo = 'TIPO_DEMANDA';
        });
    }
}
