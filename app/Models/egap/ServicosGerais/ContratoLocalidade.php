<?php

namespace App\Models\egap\ServicosGerais;

use App\Models\egap\Cadastro\Setores;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Localidade (setor) participante de um {@see Contrato}, com um `ativo` que
 * decide se ela entra na próxima geração de formulários (legado:
 * ava_localidades — tabela separada de ava_funcao, descoberta em
 * avacadastro.php/avaforms.php).
 *
 * Único por (contrato_id, setor_id).
 */
class ContratoLocalidade extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_contrato_localidades';

    public $timestamps = false;

    protected $fillable = [
        'contrato_id',
        'setor_id',
        'ativo',
        'date_time',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'date_time' => 'datetime',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
