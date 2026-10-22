<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class TermoImovel extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_termosimoveis';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

    public function termoRelacaoref()
    {
        return $this->belongsTo(TermoResponsabilidade::class, 'termo', 'id');
    }

    public function imovelRelacaoref()
    {
        return $this->belongsTo(BemImovel::class, 'imovel', 'Id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
