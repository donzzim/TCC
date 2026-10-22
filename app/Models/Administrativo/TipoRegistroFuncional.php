<?php

namespace App\Models\Administrativo;

use App\Models\Escola\Cursos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoRegistroFuncional extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function registros(): HasMany
    {
        return $this->hasMany(RegistroFuncional::class, 'tipo_id', 'id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tiporecurso', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'REGISTRO_FUNCIONAL');
        });
        static::creating(function (TipoRegistroFuncional $row) {
            $row->grupo = 'REGISTRO_FUNCIONAL';
        });
    }
}
