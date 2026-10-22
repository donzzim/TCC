<?php

namespace App\Models\egap\Patrimonio\BensIntangiveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amortizacao extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_int_amortizacao';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_intangivel',
        'data_calculo',
        'amortizacao_mensal',
        'amortizacao_acumulada',
        'valor',
        'item',
        'valor_liquido_contabil',
        'vida_util',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'data_calculo' => 'datetime',
        'amortizacao_mensal' => 'decimal:10',
        'amortizacao_acumulada' => 'decimal:10',
        'valor' => 'decimal:10',
        'valor_liquido_contabil' => 'decimal:10',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }

    public function idIntangivelRef(): BelongsTo
    {
        return $this->belongsTo(BemIntangivel::class, 'id_intangivel', 'id');
    }
}
