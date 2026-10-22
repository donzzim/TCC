<?php

namespace App\Models\egap\Cadastro;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class UnidadeGestora extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_mat_unidadegestora';

    public $timestamps = false;

    protected $guarded = [];
}
