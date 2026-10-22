<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\Fornecedores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conciliacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_conciliacao';
    public $timestamps = false;
    public static $snakeAttributes = false;

    protected $fillable = [
        'date_time',
        'numero_patrimonio',
        'descricao',
        'data_aquisicao',
        'local',
        'comarca',
        'valor_aquisicao',
        'forma_aquisicao',
        'numero_documento',
        'fornecedor',
        'data_conciliacao',
        'patrimonio',
        'patrimonio_desmembrado',
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
