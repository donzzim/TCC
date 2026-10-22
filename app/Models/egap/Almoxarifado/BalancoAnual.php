<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BalancoAnual extends Model
{
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_alm_balancoanual';
    protected $fillable = [
        'date_time',
        'data_inicio',
        'termino',
        'mensagem'
    ];


    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
