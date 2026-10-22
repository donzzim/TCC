<?php

namespace App\Models\Escola;

use App\Models\Administrativo\Agenda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoLocal extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
        'json',
    ];
    protected $casts = [
        'json' => 'array',
    ];
    public function cursos(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'tipolocal_id', 'id');
    }
    public function capacidade(): int
    {
        return $this->json['capacidade'] ?? 0;
    }
    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'local_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipolocal', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'TIPO_LOCAL');
        });
        static::creating(function (TipoLocal $row) {
            $row->grupo = 'TIPO_LOCAL';
        });
    }
}
