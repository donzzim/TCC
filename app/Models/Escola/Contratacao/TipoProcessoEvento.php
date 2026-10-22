<?php

namespace App\Models\Contratacao;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProcessoEvento extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function eventos(): HasMany
    {
        return $this->HasMany(ProcessoEvento::class, 'tipoevento_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipoevento', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'PROCESSO_EVENTOS');
        });
        static::creating(function (TipoProcessoEvento $row) {
            $row->grupo = 'PROCESSO_EVENTOS';
        });
    }
}
