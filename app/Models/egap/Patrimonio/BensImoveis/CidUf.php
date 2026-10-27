<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CidUf extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_ciduf';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'id_cidade',
        'cd_uf',
        'cd_cep_cidade'
    ];

    public function idCidadeRef(): BelongsTo
    {
        return $this->belongsTo(Cidades::class, 'id_cidade');
    }
}
