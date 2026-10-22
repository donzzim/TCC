<?php

namespace App\Models\egap\Processo;

use Illuminate\Database\Eloquent\Model;
use App\Models\egap\Patrimonio\BensImoveis\Processo;

class MatTipoProcesso extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_tipo_processo';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function processosRelacaoRef()
    {
        return $this->hasMany(Processo::class, 'id_tipo_processo', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
