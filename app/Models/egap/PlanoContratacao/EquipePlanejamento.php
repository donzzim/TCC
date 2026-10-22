<?php

namespace App\Models\egap\PlanoContratacao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipePlanejamento extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_equipe_planejamento';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id_planocontratacao' => 'integer',
        'equipe_dados' => 'array',
        'data_atualizacao' => 'datetime',
    ];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoContratacao::class, 'id_planocontratacao', 'id');
    }
}
