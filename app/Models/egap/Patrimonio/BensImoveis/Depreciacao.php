<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_depreciacao';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $guarded = ['Id'];

    public function imovelRelacaoref(): BelongsTo
    {
        return $this->belongsTo(BemImovel::class, 'Id_imovel', 'Id');
    }

    public function obraRelacaoref(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'id_obra', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
