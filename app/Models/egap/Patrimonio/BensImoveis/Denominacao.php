<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class Denominacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_denominacao';
    protected $guarded = [];
    public $timestamps = false;
}
