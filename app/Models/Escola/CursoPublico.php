<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CursoPublico extends Pivot
{
    protected $table = 'esc_cursopublico';
}
