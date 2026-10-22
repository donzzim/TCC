<?php

namespace App\Models\egap\Cadastro;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class CentroCusto extends Model
{
    //
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_cad_centrocusto';
    protected $fillable = [
        'date_time',
        'codigo',
        'descricao',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
