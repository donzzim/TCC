<?php

namespace App\Models\egap\ServicosGerais;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Usuário gestor de um {@see Contrato} (legado: ava_funcao WHERE id_tipo='G'
 * — mesmo padrão de pivot já usado em App\Models\egap\Contratacao\ProcessoGestor
 * / cnt_gestores).
 *
 * Um contrato pode ter mais de um gestor; `principal` marca o responsável
 * direto, os demais só têm acesso de visualização/gestão equivalente.
 */
class ContratoGestor extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_contrato_gestores';

    public $timestamps = false;

    protected $fillable = [
        'contrato_id',
        'user_id',
        'principal',
    ];

    protected $casts = [
        'principal' => 'boolean',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'user_id');
    }
}
