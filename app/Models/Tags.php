<?php

namespace App\Models;

use App\Models\Administrativo\Demanda;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tags extends Model
{
    protected $table = 'tags';
    protected $fillable = [
        'tag_id',
        'descricao',
        'cor',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tags::class, 'tag_id', 'id');
    }
    public function demandas(): MorphToMany
    {
        return $this->morphedByMany(Demanda::class, 'taggable', 'taggables', 'id', 'demanda_id');
    }
    public function cursos(): MorphToMany
    {
        return $this->morphedByMany(Cursos::class, 'taggable', 'taggables', 'tag_id', 'taggable_id')
            ->using(Taggable::class)
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }
    public function atividades(): MorphToMany
    {
        return $this->morphedByMany(CursoAtividades::class, 'taggable', 'taggables', 'id', 'atividade_id');
    }
}
