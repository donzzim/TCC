<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class CidUf extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_ciduf';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
}
