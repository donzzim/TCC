<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class EstadoConservacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_estadoconservacao';
    protected $primaryKey = 'Id';
    protected $guarded = [];
    public $timestamps = false;
}
