<?php

namespace App\Models\egap\PlanoContratacao;

use App\Models\Planejamento\ObjetivosEstrategicos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjetivoEstrategico extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_objetivos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function iniciativas(): HasMany
    {
        return $this->hasMany(IniciativaEstrategica::class, 'objetivo', 'id');
    }
}
