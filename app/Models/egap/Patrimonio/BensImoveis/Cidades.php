<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class Cidades extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_cidade';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'descricao',
    ];
}
