<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_depreciacao'; //
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'patrimonio',
        'item',
        'data_calculo',
        'valor',
        'vida_util',
        'valor_residual',
        'depreciacao_mensal',
        'depreciacao_acumulada',
        'valor_liquido_contabil',
    ];

    public function patrimonioRef(): BelongsTo
    {
        return $this->belongsTo(BemMovel::class, 'patrimonio', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
