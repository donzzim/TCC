<?php

namespace App\Models\egap\PlanoContratacao;

use App\Models\egap\Almoxarifado\SituacaoPedido;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HistoricoPlano extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_historico';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id_planocontratacao' => 'integer',
        'id_etapa' => 'integer',
        'id_usuario' => 'integer',
        'data_modificacao' => 'datetime',
    ];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoContratacao::class, 'id_planocontratacao', 'id');
    }

    public function situacao(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'id_etapa', 'id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_usuario', 'id');
    }

    public static function registrar(int $planoId, int $etapa, ?int $usuarioId): self
    {
        return static::create([
            'id_planocontratacao' => $planoId,
            'id_etapa' => $etapa,
            'id_usuario' => $usuarioId,
            'data_modificacao' => now(),
        ]);
    }
}
