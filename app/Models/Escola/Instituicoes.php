<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property string|null $nome */
class Instituicoes extends Model
{
    protected $table = 'esc_instituicoes';

    protected $fillable = [
        'nome',
        'link',
        'logo',
        'descricao',
    ];

    public function cursos(): HasMany
    {
        return $this->hasMany(Cursos::class, 'curso_id', 'id');
    }
}
