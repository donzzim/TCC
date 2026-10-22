<?php

namespace Modules\PlanejamentoEstrategico\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\PlanejamentoEstrategico\Database\Factories\PlanejamentoFactory;

class Planejamento extends Model
{
    use HasFactory;

    protected $table = 'pla_planejamento';
    protected $fillable = [];
}
