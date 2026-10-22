<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioComissao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_inv_comissao';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_inventario',
        'comissao',
        'nome',
        'funcao'
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function membroRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'nome', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
