<?php

namespace App\Models\egap\Processo;

use Illuminate\Database\Eloquent\Model;
use App\Models\egap\Patrimonio\BensImoveis\Processo;
use App\Models\egap\Cadastro\DescricaoResumida;

class MatAnexoProcesso extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_anexoprocesso';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function processoRelacaoRef()
    {
        return $this->belongsTo(Processo::class, 'num_processo', 'id');
    }

    public function tipoDocumentoRelacaoRef()
    {
        return $this->belongsTo(MatTipoDocumento::class, 'tipo_documento', 'id');
    }

    public function materialRelacaoRef()
    {
        return $this->belongsTo(DescricaoResumida::class, 'material', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
