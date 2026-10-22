<?php

namespace App\Models\Administrativo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusTarefa extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefas::class, 'status_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tiporecurso', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'SITUACAO_TAREFA');
        });
        static::creating(function (TipoDemandas $row) {
            $row->grupo = 'SITUACAO_TAREFA';
        });
    }
}
