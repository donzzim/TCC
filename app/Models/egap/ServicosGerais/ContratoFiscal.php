<?php

namespace App\Models\egap\ServicosGerais;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Usuário fiscal responsável por uma localidade dentro de um {@see Contrato}
 * (legado: ava_funcao WHERE id_tipo='F', que carrega id_localidade —
 * diferente do gestor, o fiscal é escopado a contrato+setor, não ao
 * contrato inteiro).
 */
class ContratoFiscal extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_contrato_fiscais';

    public $timestamps = false;

    protected $fillable = [
        'contrato_id',
        'setor_id',
        'user_id',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'user_id');
    }
}
