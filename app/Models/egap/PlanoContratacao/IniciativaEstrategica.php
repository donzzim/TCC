<?php

namespace App\Models\egap\PlanoContratacao;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IniciativaEstrategica extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_iniciativas';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'objetivo' => 'integer',
    ];

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivoEstrategico::class, 'objetivo', 'id');
    }

    public function scopeDoObjetivo(Builder $query, int $objetivoId): Builder
    {
        return $query->where('objetivo', $objetivoId);
    }
}
