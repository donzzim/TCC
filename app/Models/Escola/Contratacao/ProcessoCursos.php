<?php

namespace App\Models\Contratacao;

use App\Models\Escola\Cursos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProcessoCursos extends Model
{
    protected $table = 'cnt_processocursos';
    protected $fillable = [
        'processo_id',
        'curso_id',
        'instrutor_id',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id', 'id');
    }
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }
    public function instrutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrutor_id', 'id');
    }
}
