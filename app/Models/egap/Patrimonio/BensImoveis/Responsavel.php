<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class Responsavel extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_responsavel';
    protected $guarded = [];
    public $timestamps = false;
}
